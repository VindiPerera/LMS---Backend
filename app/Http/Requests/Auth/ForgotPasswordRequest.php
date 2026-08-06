<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class ForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    /**
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Deliberately no `exists:users,email` — that would let a caller
            // enumerate which emails are registered. Password::sendResetLink
            // already handles an unknown email safely.
            'email' => ['required', 'string', 'email'],
        ];
    }
}
