<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * Shapes a User model into the JSON the Flutter app expects.
 * Field names mirror lib/models/user.dart (AppUser) so the client
 * can decode the response directly.
 */
class UserResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'handle' => $this->handle,
            'avatarUrl' => $this->avatar_url,
            'countryFlag' => $this->country_flag,
            'nativeLang' => $this->native_lang,
            'learningLang' => $this->learning_lang,
            'isOnline' => (bool) $this->is_online,
            'isVip' => (bool) $this->is_vip,
            'age' => $this->age,
            'gender' => $this->gender,
            'bio' => $this->bio,
            'activeLabel' => $this->active_label,
            'tags' => $this->tags ?? [],
            'detail' => $this->detail,
            'profileCompleted' => (bool) $this->profile_completed,
        ];
    }
}
