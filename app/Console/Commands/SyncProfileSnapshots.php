<?php

namespace App\Console\Commands;

use App\Services\FirestoreMomentService;
use App\Services\FirestoreNotificationService;
use App\Services\FirestoreUserDirectory;
use Illuminate\Console\Command;

/**
 * Admin-side repair for the name/avatarUrl snapshots that moments and
 * notification items denormalize at write time (see FirestoreMomentService
 * and FirestoreNotificationService's class docs). Those only get repaired
 * when the AUTHOR/ACTOR themselves reopens the app — a user who changed
 * their photo but hasn't relaunched since stays stale for every viewer
 * indefinitely. Run this to fix it immediately without waiting on that.
 *
 * Usage: php artisan profile:sync-snapshots {uid?}
 * With no uid, walks every user in the directory (safe to re-run — each
 * patch is diffed against current Firestore state first, so an already
 * in-sync user costs two queries and zero writes).
 */
class SyncProfileSnapshots extends Command
{
    protected $signature = 'profile:sync-snapshots {uid? : Firebase uid to repair; omit to repair every user}';

    protected $description = 'Repairs stale name/avatarUrl snapshots on past moments and notifications';

    public function handle(
        FirestoreUserDirectory $users,
        FirestoreMomentService $moments,
        FirestoreNotificationService $notifications,
    ): int {
        $uidArg = $this->argument('uid');
        $uids = $uidArg ? [$uidArg] : $users->eachUid([]);

        $checked = 0;
        foreach ($uids as $uid) {
            $user = $users->find($uid);
            if ($user === null) {
                $this->warn("Skipping {$uid}: not found in users directory.");
                continue;
            }

            $avatarUrl = (string) ($user['avatarUrl'] ?? '');
            $name = (string) ($user['name'] ?? '');

            $momentsPatched = $moments->backfillAuthorInfo($uid, $avatarUrl, $name);
            $notifications->backfillActorAvatar($uid, $avatarUrl, $name);

            $checked++;
            if ($momentsPatched > 0) {
                $this->info("{$name} ({$uid}): repaired {$momentsPatched} moment(s).");
            }
        }

        $this->info("Done — checked {$checked} user(s).");

        return self::SUCCESS;
    }
}
