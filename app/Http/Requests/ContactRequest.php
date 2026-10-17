<?php

namespace App\Http\Requests;

use App\Support\BrazilLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ContactRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $state = strtoupper(trim((string) $this->input('state')));
        $city = trim((string) $this->input('city'));

        $this->merge([
            'city' => $city === ''
                ? null
                : (BrazilLocation::city($state, $city) ?? $city),
            'state' => $state === '' ? null : $state,
        ]);
    }

    public function rules(): array
    {
        return [
            'phone' => [
                'nullable',
                'string',
                'regex:/^[0-9()+\-\s]{10,20}$/',
            ],
            'city' => [
                'nullable',
                'required_with:state',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (
                        $value !== null
                        && ! BrazilLocation::city(
                            (string) $this->input('state'),
                            (string) $value
                        )
                    ) {
                        $fail('Selecione uma cidade válida para o estado informado.');
                    }
                },
            ],
            'state' => [
                'nullable',
                'required_with:city',
                Rule::in(array_keys(BrazilLocation::STATES)),
            ],
            'latitude' => [
                'nullable',
                'required_with:longitude',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'required_with:latitude',
                'numeric',
                'between:-180,180',
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => 'Informe o telefone com DDD, por exemplo: (55) 99999-9999.',
            'city.required_with' => 'Informe a cidade e o estado juntos.',
            'state.required_with' => 'Informe a cidade e o estado juntos.',
            'latitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
            'longitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
        ];
    }
}