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
use Illuminate\Support\LazyCollection;
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
 * user). Queued because this can mean tens of thousands of writes/sends —
 * the request that created the Broadcast row returns immediately, this
 * does the actual work afterward (see admin.php's queue worker
 * requirement).
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
     * @return \Generator<int, list<string>>
     */
    private function uidChunks(Broadcast $broadcast, FirestoreUserDirectory $directory): \Generator
    {
        foreach (LazyCollection::make($directory->eachUid($broadcast->audience_filters))->chunk(250) as $chunk) {
            yield $chunk->values()->all();
        }
    }
}
