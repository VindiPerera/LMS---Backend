<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Rules\Password;

class RegisterRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        // Anyone may attempt to register.
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Signup no longer collects a display name — that's set
            // afterwards on create_profile_screen.dart — so this is only
            // honored if a caller happens to send one; AuthController
            // falls back to an email-derived placeholder otherwise.
            'name' => ['sometimes', 'nullable', 'string', 'max:255'],
            'email' => ['required', 'string', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'confirmed', Password::min(8)],
            // Matches FaceTalkRole in signup_screen.dart.
            'role' => ['required', Rule::in(['student', 'teacher'])],
        ];
    }
}
