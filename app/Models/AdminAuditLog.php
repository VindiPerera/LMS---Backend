<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;

#[Fillable(['admin_id', 'action', 'target_type', 'target_id', 'meta'])]
class AdminAuditLog extends Model
{
    protected function casts(): array
    {
        return [
            'meta' => 'array',
        ];
    }

    public function admin()
    {
        return $this->belongsTo(Admin::class);
    }

    /**
     * Record one admin action. $target is any model (or null for actions
     * with no single target, e.g. a broadcast to a filtered audience).
     */
    public static function record(Admin $admin, string $action, ?Model $target = null, array $meta = []): self
    {
        return static::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $target?->getMorphClass(),
            'target_id' => $target?->getKey(),
            'meta' => $meta,
        ]);
    }

    /**
     * Same as record(), for a target that isn't an Eloquent model — the
     * app's real users are Firebase Auth / Firestore records (see
     * FirestoreUserDirectory), identified by uid string rather than an
     * Eloquent key.
     */
    public static function recordFor(Admin $admin, string $action, ?string $targetType, ?string $targetId, array $meta = []): self
    {
        return static::create([
            'admin_id' => $admin->id,
            'action' => $action,
            'target_type' => $targetType,
            'target_id' => $targetId,
            'meta' => $meta,
        ]);
    }
}
