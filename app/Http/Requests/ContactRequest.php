<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        if ($this->filled('state')) {
            $this->merge(['state' => strtoupper($this->state)]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'regex:/^[0-9()+\-\s]{10,20}$/'],
            'city' => ['nullable', 'string', 'max:100'],
            'state' => ['nullable', 'string', 'size:2'],
            'latitude' => ['nullable', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return ['phone.regex' => 'Informe o telefone com DDD, ex: (55) 99999-9999.'];
    }
}
