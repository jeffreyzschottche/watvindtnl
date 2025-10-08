<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateUserRequest extends FormRequest
{
    public function authorize(): bool
    {
        /** @var \App\Models\User|null $user */
        $user = $this->route('user');

        return $user !== null && $this->user()?->id === $user->id;
    }

    public function rules(): array
    {
        return [
            'name' => ['sometimes', 'string', 'max:60'],
            'email' => [
                'sometimes',
                'email',
                'max:255',
                Rule::unique('users', 'email')->ignore($this->route('user')?->id),
            ],
            'language' => ['sometimes', 'string', 'in:nl,en'],
            'age' => ['sometimes', 'nullable', 'integer', 'min:0', 'max:120'],
            'province' => ['sometimes', 'nullable', 'string', 'max:120'],
            'gender' => ['sometimes', 'in:male,female,unspecified'],
            'education_level' => ['sometimes', 'nullable', 'in:mbo,hbo,universiteit,overig'],
            'political_preference' => ['sometimes', 'nullable', 'in:links,midden,rechts,geen'],
            'premium' => ['sometimes', 'boolean'],
        ];
    }
}
