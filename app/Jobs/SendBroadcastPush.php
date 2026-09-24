<?php

namespace App\Jobs;

use App\Models\Broadcast;
use App\Models\FcmToken;
use App\Services\FirestoreChatBroadcastService;
use App\Services\FirestoreUserDirectory;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;
use Illuminate\Support\Facades\Log;
use Kreait\Firebase\Contract\Messaging;
use Kreait\Firebase\Messaging\CloudMessage;

/**
 * Delivers one Broadcast to its whole matched audience, two ways:
 *
 *   1. A "FaceTalk" chat message (FirestoreChatBroadcastService) — every
 *      matched user gets this, like an in-app announcement.
 *   2. A push notification — only the subset who also have a saved FCM
 *      token (a Firestore match with no token just can't be reached that
 *      way; they still get the chat message).
 *
 * Audience = FirestoreUserDirectory's matching uids (no filters = every
 * user), or, for Admin\BroadcastController's specific_users audience
 * type, an exact hand-picked uid list (see uidChunks()).
 *
 * Still a real ShouldQueue job (this can mean tens of thousands of
 * writes/sends, which belongs off the request thread) — but
 * BroadcastController::store() currently runs it via dispatchSync()
 * rather than dispatch(), because this project has no deployed
 * queue-worker process yet, so a real queued dispatch would just sit in
 * the `jobs` table forever. Once real hosting exists with a worker
 * (`php artisan queue:work` under Supervisor, or `queue:work
 * --stop-when-empty` on a schedule), switching store() back to
 * dispatch() is the only change needed — nothing here has to.
 */
class SendBroadcastPush implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    /**
     * One attempt — sent/failed counts update incrementally as chunks
     * complete, so a retry would re-send to everyone already reached
     * instead of resuming. Fix forward from the admin panel instead.
     */
    public $tries = 1;

    public $timeout = 3600;

    public function __construct(public int $broadcastId)
    {
    }

    public function handle(
        FirestoreUserDirectory $directory,
        FirestoreChatBroadcastService $chatBroadcast,
        Messaging $messaging,
    ): void {
        $broadcast = Broadcast::findOrFail($this->broadcastId);
        // forceFill, not update(): dispatched_at/completed_at/sent_count/
        // failed_count/error are this job's own bookkeeping, deliberately
        // left out of Broadcast's $fillable (which only covers what the
        // admin compose form legitimately sets) — update() would silently
        // no-op on them under mass-assignment protection.
        $broadcast->forceFill(['status' => 'sending', 'dispatched_at' => now()])->save();

        $pushMessage = CloudMessage::fromArray([
            'notification' => ['title' => $broadcast->title, 'body' => $broadcast->body],
            'android' => [
                'priority' => 'high',
                'notification' => [
                    // Reuses the one high-importance channel guaranteed to
                    // exist client-side (push_notification_service.dart) —
                    // there's no dedicated "announcements" channel yet.
                    'channel_id' => 'moments_channel',
                    'notification_priority' => 'PRIORITY_MAX',
                    'visibility' => 'PUBLIC',
                    'default_sound' => true,
                    'default_vibrate_timings' => true,
                ],
            ],
            'apns' => [
                'payload' => ['aps' => ['sound' => 'default', 'content-available' => 1]],
            ],
            'data' => ['type' => 'broadcast'],
        ]);

        // Chat bubbles don't have a separate title field — fold it into the
        // message text the same way a push notification shows both.
        $chatText = trim($broadcast->title) !== ''
            ? "{$broadcast->title}\n\n{$broadcast->body}"
            : $broadcast->body;

        $sent = 0;
        $failed = 0;

        try {
            foreach ($this->uidChunks($broadcast, $directory) as $uids) {
                $chatBroadcast->sendToMany($uids, $chatText);

                $tokens = FcmToken::whereIn('uid', $uids)->pluck('token');
                if ($tokens->isNotEmpty()) {
                    $report = $messaging->sendMulticast($pushMessage, $tokens->all());
                    $sent += $report->successes()->count();
                    $failed += $report->failures()->count();

                    $stale = array_merge($report->invalidTokens(), $report->unknownTokens());
                    if ($stale) {
                        FcmToken::whereIn('token', $stale)->delete();
                    }
                }

                $broadcast->forceFill(['sent_count' => $sent, 'failed_count' => $failed])->save();
            }

            $broadcast->forceFill(['status' => 'completed', 'completed_at' => now()])->save();
        } catch (\Throwable $e) {
            Log::error("Broadcast #{$broadcast->id} failed: {$e->getMessage()}");
            $broadcast->forceFill(['status' => 'failed', 'error' => $e->getMessage(), 'completed_at' => now()])->save();
        }
    }

    /**
     * 250 per chunk — the binding constraint is FirestoreChatBroadcastService's
     * :commit batch (2 writes/uid, 500-write cap), not the push side, which
     * comfortably handles chunks this size too.
     *
     * A "Specific users…" broadcast (Admin\BroadcastController's
     * specific_users audience type) already has its exact recipient list —
     * audience_filters['uids'] — so this chunks that directly instead of
     * asking FirestoreUserDirectory to resolve who matches a filter; there
     * is no filter to resolve for this audience type.
     *
     * Plain manual accumulate-and-flush over eachUid()'s generator here —
     * not LazyCollection::make($generator)->chunk(...) (what this used to
     * be): Laravel's LazyCollection explicitly rejects a raw Generator
     * instance ("Generators should not be passed directly to
     * LazyCollection. Instead, pass a generator function.") — a
     * pre-existing bug that had simply never run before, since nothing
     * ever executed this job until dispatchSync() started actually
     * running it (see class doc comment). Same shape as
     * FirestoreChatBroadcastService::sendToMany's own chunking.
     *
     * @return \Generator<int, list<string>>
     */
    private function uidChunks(Broadcast $broadcast, FirestoreUserDirectory $directory): \Generator
    {
        $uids = $broadcast->audience_filters['uids'] ?? null;
        if ($uids !== null) {
            foreach (array_chunk($uids, 250) as $chunk) {
                yield $chunk;
            }

            return;
        }

        $chunk = [];
        foreach ($directory->eachUid($broadcast->audience_filters) as $uid) {
            $chunk[] = $uid;
            if (count($chunk) >= 250) {
                yield $chunk;
                $chunk = [];
            }
        }
        if ($chunk) {
            yield $chunk;
        }
    }
}
