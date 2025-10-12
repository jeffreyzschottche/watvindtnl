<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email'],
            'message' => ['required', 'string', 'max:2000'],
            'altcha.token' => ['required', 'string'],
            'altcha.challenge' => ['nullable', 'string'],
            'altcha.answer' => ['nullable', 'string'],
            'altcha.signature' => ['nullable', 'string'],
        ];
    }
}
