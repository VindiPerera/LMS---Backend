<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read/moderate access to Firestore's `reports/{reportId}` collection —
 * written by the Flutter app's ReportService (report_sheet.dart for a
 * Moments post, room_profile_sheet.dart's "Report" action for a voice room
 * participant). firestore.rules makes this collection client-write-only
 * (create-only, no client read/update/delete) — this service uses the same
 * service-account REST approach as FirestoreUserDirectory/
 * FirestoreChatBroadcastService to read and update it from the admin panel,
 * bypassing those rules exactly the way FirestoreUserDirectory::updateFields
 * already does for `users/{uid}`.
 *
 * Reports predate having a `status` field — every report written before
 * this admin module existed has none, so it's always treated as 'pending'
 * when missing rather than defaulting to some other display.
 *
 * v1 scope: list() filters by status entirely client-side (in PHP, after
 * fetching a createdAt-ordered page) rather than adding a Firestore `where`
 * clause — a `status` equality filter wouldn't match those legacy
 * status-less documents anyway, and doing it this way avoids needing a new
 * composite index (status + createdAt) deployed against the live project.
 * The trade-off: a filtered page can come back with fewer than $limit rows
 * even when more matching reports exist further down — acceptable for the
 * report volumes this panel deals with; "Next page" still finds them.
 */
class FirestoreReportService
{
    public const STATUSES = ['pending', 'reviewed', 'resolved', 'dismissed'];

    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-52f9b');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    /**
     * One page of reports, newest first, optionally filtered by status
     * (see class doc for why that filter is applied in PHP, not Firestore).
     * $afterCreatedAt pages forward from a previous result's `next_cursor`
     * (the raw RFC3339 createdAt of the last row returned).
     *
     * @return array{reports: list<array>, next_cursor: ?string}
     */
    public function list(?string $status = null, int $limit = 20, ?string $afterCreatedAt = null): array
    {
        $structuredQuery = [
            'from' => [['collectionId' => 'reports']],
            'orderBy' => [['field' => ['fieldPath' => 'createdAt'], 'direction' => 'DESCENDING']],
            'limit' => $limit,
        ];
        if ($afterCreatedAt) {
            $structuredQuery['startAt'] = [
                'values' => [['timestampValue' => $afterCreatedAt]],
                'before' => false,
            ];
        }

        $response = $this->post(':runQuery', ['structuredQuery' => $structuredQuery]);

        $reports = [];
        foreach ($response->json() ?? [] as $row) {
            if (!isset($row['document'])) {
                continue; // heartbeat row, no document on this page
            }
            $report = $this->decodeReport($row['document']);
            if ($status !== null && $status !== '' && $report['status'] !== $status) {
                continue;
            }
            $reports[] = $report;
        }

        $nextCursor = count($reports) === $limit ? ($reports[array_key_last($reports)]['createdAt'] ?? null) : null;

        return ['reports' => $reports, 'next_cursor' => $nextCursor];
    }

    /**
     * Every report about $uid, newest first — sorted in PHP rather than via
     * Firestore `orderBy` (see class doc) so this needs no composite index;
     * a single user's report count is always small enough to fetch in one
     * page.
     *
     * @return list<array>
     */
    public function forUser(string $uid): array
    {
        $structuredQuery = [
            'from' => [['collectionId' => 'reports']],
            'where' => $this->fieldFilter('reportedUserId', 'EQUAL', $uid),
            'limit' => 500,
        ];

        $response = $this->post(':runQuery', ['structuredQuery' => $structuredQuery]);

        $reports = [];
        foreach ($response->json() ?? [] as $row) {
            if (!isset($row['document'])) {
                continue;
            }
            $reports[] = $this->decodeReport($row['document']);
        }

        usort($reports, fn ($a, $b) => strcmp($b['createdAt'] ?? '', $a['createdAt'] ?? ''));

        return $reports;
    }

    /**
     * Fast total-report-count for a user (shown next to their profile) —
     * Firestore's aggregation query counts server-side without fetching
     * every matching document.
     */
    public function countForUser(string $uid): int
    {
        $response = $this->post(':runAggregationQuery', [
            'structuredAggregationQuery' => [
                'structuredQuery' => [
                    'from' => [['collectionId' => 'reports']],
                    'where' => $this->fieldFilter('reportedUserId', 'EQUAL', $uid),
                ],
                'aggregations' => [['alias' => 'count', 'count' => (object) []]],
            ],
        ]);

        $rows = $response->json() ?? [];

        return (int) ($rows[0]['result']['aggregateFields']['count']['integerValue'] ?? 0);
    }

    /**
     * Sets a report's moderation status. Client-side create()d reports have
     * no `status` field at all, and firestore.rules blocks any client
     * update — this is the only place one is ever set, via the
     * service-account credential (same bypass FirestoreUserDirectory::
     * updateFields already relies on for `users/{uid}`).
     */
    public function updateStatus(string $reportId, string $status): void
    {
        $response = Http::withToken($this->accessToken())->patch(
            $this->documentUrl("/reports/{$reportId}").'?updateMask.fieldPaths=status',
            ['fields' => ['status' => ['stringValue' => $status]]],
        );

        if (!$response->successful()) {
            throw new RuntimeException('Failed to update Firestore report: '.$response->body());
        }
    }

    private function fieldFilter(string $field, string $op, mixed $value): array
    {
        return ['fieldFilter' => [
            'field' => ['fieldPath' => $field],
            'op' => $op,
            'value' => $this->encodeValue($value),
        ]];
    }

    private function documentUrl(string $path): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents{$path}";
    }

    private function post(string $suffix, array $body)
    {
        $response = Http::withToken($this->accessToken())
            ->post($this->documentUrl('').$suffix, $body);

        if (!$response->successful()) {
            throw new RuntimeException('Firestore query failed: '.$response->body());
        }

        return $response;
    }

    private function accessToken(): string
    {
        return Cache::remember('firestore_access_token', now()->addMinutes(50), function () {
            if (!$this->credentialsPath || !file_exists(base_path($this->credentialsPath))) {
                throw new RuntimeException(
                    'Firebase credentials not found — see FIREBASE_CREDENTIALS in .env.'
                );
            }

            $credentials = new ServiceAccountCredentials(
                'https://www.googleapis.com/auth/datastore',
                base_path($this->credentialsPath),
            );
            $token = $credentials->fetchAuthToken();

            return $token['access_token'];
        });
    }

    private function decodeReport(array $document): array
    {
        $fields = $this->decodeFields($document['fields'] ?? []);
        $fields['id'] = basename($document['name'] ?? '');
        $fields['status'] = $fields['status'] ?? 'pending';

        return $fields;
    }

    private function decodeFields(array $fields): array
    {
        return collect($fields)->map(fn ($value) => $this->decodeValue($value))->all();
    }

    private function decodeValue(array $value): mixed
    {
        return match (true) {
            array_key_exists('stringValue', $value) => $value['stringValue'],
            array_key_exists('booleanValue', $value) => $value['booleanValue'],
            array_key_exists('integerValue', $value) => (int) $value['integerValue'],
            array_key_exists('doubleValue', $value) => (float) $value['doubleValue'],
            array_key_exists('timestampValue', $value) => $value['timestampValue'],
            array_key_exists('nullValue', $value) => null,
            array_key_exists('arrayValue', $value) => array_map(
                fn ($v) => $this->decodeValue($v),
                $value['arrayValue']['values'] ?? [],
            ),
            array_key_exists('mapValue', $value) => $this->decodeFields($value['mapValue']['fields'] ?? []),
            default => null,
        };
    }

    private function encodeValue(mixed $value): array
    {
        return match (true) {
            is_bool($value) => ['booleanValue' => $value],
            is_int($value) => ['integerValue' => (string) $value],
            is_float($value) => ['doubleValue' => $value],
            is_null($value) => ['nullValue' => null],
            is_array($value) => ['arrayValue' => ['values' => array_map(fn ($v) => $this->encodeValue($v), $value)]],
            default => ['stringValue' => (string) $value],
        };
    }
}
