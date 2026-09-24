<?php

namespace App\Services;

use Google\Auth\Credentials\ServiceAccountCredentials;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Http;
use RuntimeException;

/**
 * Read/search/edit access to Firestore's `users/{uid}` collection — the
 * app's real user directory (see AppUser.fromJson in lib/models/user.dart).
 * The Laravel `users` table is a separate, currently-unused identity system
 * (nothing in the Flutter app calls /api/login, /register, or /me) — admin
 * user management and bulk-message audience filtering both read from here
 * instead, keyed by Firebase Auth uid, same as `fcm_tokens` and `payments`.
 *
 * Uses the Firestore REST API directly, same approach and same service
 * account credentials as FirestoreVipService (see that class's doc for why
 * REST over the full gRPC client library) — duplicated here rather than
 * shared, to avoid touching that already-working payment-flow code.
 *
 * v1 scope: one filter dimension at a time (search OR role OR VIP OR
 * language — never combined). Firestore serves a single-field filter off
 * its automatic per-field index with no setup; combining filters needs a
 * composite index declared in firestore.indexes.json and deployed, which
 * isn't done for `users` yet. Extend this once that's in place.
 */
class FirestoreUserDirectory
{
    private string $projectId;
    private ?string $credentialsPath;

    public function __construct()
    {
        $this->projectId = (string) config('services.firebase.project_id', 'hello-52f9b');
        $this->credentialsPath = config('firebase.projects.app.credentials');
    }

    /**
     * One page of the `users` collection. $filters may set at most one of:
     * 'search' (matches email exactly, or name by prefix), 'role', 'is_vip'
     * ('1'/'0'), 'native_lang', 'learning_lang'. $afterId pages forward from
     * a previous result's `next_cursor`.
     *
     * @return array{users: list<array>, next_cursor: ?string}
     */
    public function search(array $filters, int $limit = 20, ?string $afterId = null): array
    {
        ['orderField' => $orderField, 'where' => $where] = $this->buildQuery($filters);

        $structuredQuery = [
            'from' => [['collectionId' => 'users']],
            'orderBy' => [['field' => ['fieldPath' => $orderField], 'direction' => 'ASCENDING']],
            'limit' => $limit,
        ];
        if ($where) {
            $structuredQuery['where'] = $where;
        }
        if ($afterId) {
            // Cursor is the last row's own field value (name, or document id
            // when unfiltered) — startAfter needs one value per orderBy field.
            $cursorValue = $orderField === '__name__'
                ? $this->documentPath($afterId)
                : $afterId;
            $structuredQuery['startAt'] = [
                'values' => [$orderField === '__name__' ? ['referenceValue' => $cursorValue] : ['stringValue' => $cursorValue]],
                'before' => false,
            ];
        }

        $response = $this->post(':runQuery', ['structuredQuery' => $structuredQuery]);

        $users = [];
        foreach ($response->json() ?? [] as $row) {
            if (!isset($row['document'])) {
                continue; // heartbeat row, no document on this page
            }
            $users[] = $this->decodeDocument($row['document']);
        }

        $nextCursor = count($users) === $limit ? ($users[array_key_last($users)]['id'] ?? null) : null;

        return ['users' => $users, 'next_cursor' => $nextCursor];
    }

    /**
     * Fast recipient-count estimate for the broadcast composer — Firestore's
     * aggregation query counts server-side without reading every matching
     * document, so this stays cheap even across ~100k users.
     */
    public function count(array $filters): int
    {
        ['where' => $where] = $this->buildQuery($filters);

        $structuredQuery = ['from' => [['collectionId' => 'users']]];
        if ($where) {
            $structuredQuery['where'] = $where;
        }

        $response = $this->post(':runAggregationQuery', [
            'structuredAggregationQuery' => [
                'structuredQuery' => $structuredQuery,
                'aggregations' => [['alias' => 'count', 'count' => (object) []]],
            ],
        ]);

        $rows = $response->json() ?? [];

        return (int) ($rows[0]['result']['aggregateFields']['count']['integerValue'] ?? 0);
    }

    /**
     * Yields every uid matching $filters across as many pages as needed —
     * for bulk sends (see App\Jobs\SendBroadcastPush), unlike search()'s
     * one-page-at-a-time admin-UI use.
     *
     * @return \Generator<int, string>
     */
    public function eachUid(array $filters, int $pageSize = 1000): \Generator
    {
        $cursor = null;
        do {
            $page = $this->search($filters, $pageSize, $cursor);
            foreach ($page['users'] as $user) {
                yield $user['id'];
            }
            $cursor = $page['next_cursor'];
        } while ($cursor);
    }

    public function find(string $uid): ?array
    {
        $response = $this->get("/users/{$uid}");
        if ($response->status() === 404) {
            return null;
        }
        if (!$response->successful()) {
            throw new RuntimeException('Failed to read Firestore user: '.$response->body());
        }

        return $this->decodeDocument($response->json());
    }

    /**
     * Patches only the given fields on `users/{uid}` — never overwrites the
     * whole document (Firestore's updateMask makes that safe alongside the
     * many other things that write to this same doc: the Flutter app,
     * FirestoreVipService, Cloud Firestore rules-side defaults, etc.).
     */
    public function updateFields(string $uid, array $fields): void
    {
        $mask = collect(array_keys($fields))
            ->map(fn ($field) => 'updateMask.fieldPaths='.urlencode($field))
            ->implode('&');

        $response = Http::withToken($this->accessToken())->patch(
            $this->documentUrl("/users/{$uid}")."?{$mask}",
            ['fields' => collect($fields)->map(fn ($v) => $this->encodeValue($v))->all()],
        );

        if (!$response->successful()) {
            throw new RuntimeException('Failed to update Firestore user: '.$response->body());
        }
    }

    /**
     * Deletes `users/{uid}` outright — used only alongside deleting the
     * matching Firebase Auth account (UserController::destroy), never on
     * its own. Scope note: this is the core profile document only, not a
     * cascade of every moment/message/room-history doc that uid ever
     * touched — see UserController::destroy's own doc for why.
     */
    public function delete(string $uid): void
    {
        $response = Http::withToken($this->accessToken())->delete($this->documentUrl("/users/{$uid}"));

        if (!$response->successful() && $response->status() !== 404) {
            throw new RuntimeException('Failed to delete Firestore user: '.$response->body());
        }
    }

    /**
     * Shared by search() and count(): translates the admin-facing filter
     * set into a Firestore `where` clause, plus which field it needs to be
     * ordered by (only matters for search()'s pagination cursor).
     *
     * @return array{orderField: string, where: ?array}
     */
    private function buildQuery(array $filters): array
    {
        $search = trim((string) ($filters['search'] ?? ''));
        $role = (string) ($filters['role'] ?? '');
        $isVip = (string) ($filters['is_vip'] ?? '');
        $nativeLang = (string) ($filters['native_lang'] ?? '');
        $learningLang = (string) ($filters['learning_lang'] ?? '');

        if ($search !== '' && str_contains($search, '@')) {
            return ['orderField' => '__name__', 'where' => $this->fieldFilter('email', 'EQUAL', $search)];
        }
        if ($search !== '') {
            return ['orderField' => 'name', 'where' => $this->compositeAnd([
                $this->fieldFilter('name', 'GREATER_THAN_OR_EQUAL', $search),
                $this->fieldFilter('name', 'LESS_THAN', $search."\u{f8ff}"),
            ])];
        }
        if ($role !== '') {
            return ['orderField' => '__name__', 'where' => $this->fieldFilter('role', 'EQUAL', $role)];
        }
        if ($isVip !== '') {
            return ['orderField' => '__name__', 'where' => $this->fieldFilter('isVip', 'EQUAL', $isVip === '1')];
        }
        if ($nativeLang !== '') {
            return ['orderField' => '__name__', 'where' => $this->fieldFilter('nativeLang', 'EQUAL', $nativeLang)];
        }
        if ($learningLang !== '') {
            return ['orderField' => '__name__', 'where' => $this->fieldFilter('learningLang', 'EQUAL', $learningLang)];
        }

        return ['orderField' => '__name__', 'where' => null];
    }

    private function fieldFilter(string $field, string $op, mixed $value): array
    {
        return ['fieldFilter' => [
            'field' => ['fieldPath' => $field],
            'op' => $op,
            'value' => $this->encodeValue($value),
        ]];
    }

    private function compositeAnd(array $filters): array
    {
        return ['compositeFilter' => ['op' => 'AND', 'filters' => $filters]];
    }

    private function documentPath(string $uid): string
    {
        return "projects/{$this->projectId}/databases/(default)/documents/users/{$uid}";
    }

    private function documentUrl(string $path): string
    {
        return "https://firestore.googleapis.com/v1/projects/{$this->projectId}/databases/(default)/documents{$path}";
    }

    private function get(string $path)
    {
        return Http::withToken($this->accessToken())->get($this->documentUrl($path));
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

    private function decodeDocument(array $document): array
    {
        $fields = $this->decodeFields($document['fields'] ?? []);
        $fields['id'] = basename($document['name'] ?? '');

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
