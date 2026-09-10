<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Str;

/**
 * A short redeemable code minted for one target (currently only
 * `type: 'add_friend'`, `uid: <friend's Firebase uid>`) — see
 * DeepLinkController for how it's created/resolved and this migration's
 * doc comment for why `payload` exists instead of a live data lookup.
 */
#[Fillable(['code', 'type', 'uid', 'payload', 'expires_at'])]
class DeepLink extends Model
{
    protected function casts(): array
    {
        return [
            'payload' => 'array',
            'expires_at' => 'datetime',
        ];
    }

    public function isExpired(): bool
    {
        return $this->expires_at !== null && $this->expires_at->isPast();
    }

    /**
     * Generates a short, URL-safe, human-typeable code (mixed-case
     * alphanumeric, e.g. "AbC123x9") and retries on the (astronomically
     * unlikely) chance of a collision with an existing row.
     */
    public static function generateUniqueCode(int $length = 8): string
    {
        do {
            $code = Str::random($length);
        } while (static::where('code', $code)->exists());

        return $code;
    }
}
