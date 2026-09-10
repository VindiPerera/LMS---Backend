<?php

namespace App\Models;

// use Illuminate\Contracts\Auth\MustVerifyEmail;
use App\Notifications\ResetPasswordNotification;
use Database\Factories\UserFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Attributes\Hidden;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Laravel\Sanctum\HasApiTokens;

#[Fillable([
    'name',
    'email',
    'password',
    'google_id',
    'role',
    'handle',
    'avatar_url',
    'country_flag',
    'native_lang',
    'learning_lang',
    'is_online',
    'is_vip',
    'age',
    'gender',
    'bio',
    'active_label',
    'tags',
    'detail',
    'profile_completed',
])]
#[Hidden(['password', 'remember_token'])]
class User extends Authenticatable
{
    /** @use HasFactory<UserFactory> */
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * Get the attributes that should be cast.
     *
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'email_verified_at' => 'datetime',
            'password' => 'hashed',
            'is_online' => 'boolean',
            'is_vip' => 'boolean',
            'profile_completed' => 'boolean',
            'tags' => 'array',
            'banned_at' => 'datetime',
        ];
    }

    /**
     * Whether an admin has banned this account (see the admin panel's user
     * management screen). Checked at API login.
     */
    public function isBanned(): bool
    {
        return $this->banned_at !== null;
    }

    /**
     * Send the "Forgot password" email — overridden to mail a plain code
     * (see ResetPasswordNotification) instead of the stock notification's
     * link, since there's no web password-reset page in this API-only app.
     */
    public function sendPasswordResetNotification($token): void
    {
        $this->notify(new ResetPasswordNotification($token));
    }
}
