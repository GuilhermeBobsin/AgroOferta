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
        if ($this->has('city')) {
            $this->merge(['city' => trim((string) $this->city) ?: null]);
        }

        if ($this->filled('state')) {
            $this->merge(['state' => strtoupper(trim($this->state))]);
        }
    }

    public function rules(): array
    {
        return [
            'phone' => ['nullable', 'string', 'regex:/^[0-9()+\-\s]{10,20}$/'],
            'city' => ['nullable', 'required_with:state', 'string', 'max:100'],
            'state' => ['nullable', 'required_with:city', 'regex:/^[A-Z]{2}$/'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Informe o telefone com DDD, por exemplo: (55) 99999-9999.',
            'city.required_with' => 'Informe a cidade e o estado juntos.',
            'state.required_with' => 'Informe a cidade e o estado juntos.',
            'state.regex' => 'Informe uma sigla de estado válida com duas letras.',
            'latitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
            'longitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
        ];
    }
}
