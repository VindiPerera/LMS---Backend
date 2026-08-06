<?php

namespace App\Http\Requests\Auth;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class GoogleLoginRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
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
            // The Google ID token (JWT) returned to the Flutter client by
            // Google Identity Services after "Continue with Google".
            'id_token' => ['required', 'string'],
            // Only used the first time this Google account signs in here,
            // to match FaceTalkRole in signup_screen.dart; ignored for an
            // existing account.
            'role' => ['sometimes', 'nullable', Rule::in(['student', 'teacher'])],
        ];
    }
}
