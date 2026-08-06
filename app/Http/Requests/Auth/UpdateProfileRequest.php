<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateProfileRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Any authenticated user (enforced by the auth:sanctum middleware) may
        // update their own profile.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        $userId = $this->user()->id;

        return [
            // CreateProfileScreen: "Display Name"
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'handle' => ['sometimes', 'nullable', 'string', 'max:255', Rule::unique('users', 'handle')->ignore($userId)],
            'avatar_url' => ['sometimes', 'nullable', 'string', 'max:2048'],
            'country_flag' => ['sometimes', 'nullable', 'string', 'max:8'],
            // CreateProfileScreen: "Native Language" / "Learning" dropdowns
            'native_lang' => ['sometimes', 'nullable', 'string', 'max:64'],
            'learning_lang' => ['sometimes', 'nullable', 'string', 'max:64'],
            // CreateProfileScreen: "Short Bio"
            'bio' => ['sometimes', 'nullable', 'string', 'max:1000'],
            // CreateProfileScreen: "Subject you teach" (teacher) / "Interests / Grade" (student)
            'detail' => ['sometimes', 'nullable', 'string', 'max:255'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:150'],
            'gender' => ['sometimes', 'nullable', 'string', Rule::in(['male', 'female', 'other'])],
            'tags' => ['sometimes', 'nullable', 'array'],
            'tags.*' => ['string', 'max:64'],
        ];
    }
}
