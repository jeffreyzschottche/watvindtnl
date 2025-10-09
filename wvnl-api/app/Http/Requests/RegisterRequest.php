<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class RegisterRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:60'],
            'username' => ['required', 'string', 'max:40', 'unique:users,username'],
            'email' => ['required', 'email', 'unique:users,email'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
            'password_confirmation' => ['required', 'string', 'min:8'],
            'language' => ['required', 'in:nl,en'],
            'age_category' => [
                'required',
                Rule::in(['16-17', '18-24', '25-34', '35-44', '45-54', '55-64', '65+']),
            ],
            'province' => ['required', 'string'],
            'gender' => ['required', 'in:male,female,unspecified'],
            'education_level' => ['required', 'in:mbo,hbo,universiteit,overig'],
            'political_preference' => ['required', 'in:links,midden,rechts,geen'],
            'notification_prefs' => ['sometimes', 'array'],
            'notification_prefs.email' => ['sometimes', 'boolean'],
            'cookie_prefs' => ['required', 'array'],
            'cookie_prefs.accepted' => ['required', 'accepted'],
            'cookie_prefs.accepted_at' => ['sometimes', 'date'],
            'allergies' => ['sometimes', 'array'],
            'allergies.*' => ['string'],
            'special_notes' => ['sometimes', 'array'],
            'special_notes.*' => ['string'],
        ];
    }
}
