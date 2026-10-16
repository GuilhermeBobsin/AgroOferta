<?php

namespace App\Http\Requests;

use App\Models\Listing;
use App\Support\BrazilLocation;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

/** Usado na criação (sem rota {listing}) e na edição (dono do anúncio). */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        return ! $listing || $listing->user_id === $this->user()?->id;
    }

    protected function prepareForValidation(): void
    {
        $state = strtoupper(trim((string) $this->input('state')));
        $city = trim((string) $this->input('city'));

        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => $this->filled('description')
                ? trim((string) $this->input('description'))
                : null,
            'city' => $city === ''
                ? null
                : (BrazilLocation::city($state, $city) ?? $city),
            'state' => $state === '' ? null : $state,
            'negotiable' => $this->boolean('negotiable'),
        ]);
    }

    public function rules(): array
    {
        return [
            'title' => ['required', 'string', 'max:255'],
            'description' => ['nullable', 'string', 'max:5000'],
            'price' => ['required', 'numeric', 'min:0', 'max:9999999999.99'],
            'unit' => ['required', 'in:'.implode(',', Listing::UNITS)],
            'quantity' => ['nullable', 'numeric', 'min:0', 'max:9999999999'],
            'negotiable' => ['boolean'],
            'city' => [
                'required',
                'string',
                'max:100',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    if (! BrazilLocation::city(
                        (string) $this->input('state'),
                        (string) $value
                    )) {
                        $fail('Selecione uma cidade válida para o estado informado.');
                    }
                },
            ],
            'state' => [
                'required',
                Rule::in(array_keys(BrazilLocation::STATES)),
            ],
            'latitude' => [
                'nullable',
                'numeric',
                'between:-90,90',
            ],
            'longitude' => [
                'nullable',
                'numeric',
                'between:-180,180',
            ],
            'image' => [
                'nullable',
                'image',
                'mimes:jpg,jpeg,png,webp',
                'max:4096',
            ],
            'category_id' => [
                'required',
                'integer',
                Rule::exists('categories', 'id'),
            ],
        ];
    }

    public function messages(): array
    {
        return [
            'state.in' => 'Selecione um estado válido.',
            'price.between' => 'O preço precisa estar entre R$ 0,00 e R$ 9.999.999.999,99.',
            'quantity.between' => 'A quantidade informada é muito alta.',
            'latitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
            'longitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
        ];
    }
}