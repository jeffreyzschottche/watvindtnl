<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:60'],
            'email' => ['sometimes', 'email', 'unique:users,email,' . $this->route('user')->id],
            'password' => ['nullable', 'string', 'min:8'],
            'username' => ['sometimes', 'string', 'max:40', 'nullable'],
            'voted_issue_ids' => ['sometimes', 'array'],
            'voted_issue_ids.*' => ['integer'],
            'requests' => ['sometimes', 'array'],
            'requests.*' => ['string'],
            'age_category' => ['sometimes', 'string', 'nullable'],
            'province' => ['sometimes', 'string', 'nullable'],
            'gender' => ['sometimes', 'in:male,female,unspecified'],
            'education_level' => ['sometimes', 'string', 'nullable'],
            'political_preference' => ['sometimes', 'string', 'nullable'],
            'notification_prefs' => ['sometimes', 'array'],
            'cookie_prefs' => ['sometimes', 'array'],
            'language' => ['sometimes', 'in:nl,en'],
        ];
    }
}
