<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;

/** Usado na criação (sem rota {listing}) e na edição (dono do anúncio). */
class ListingRequest extends FormRequest
{
    public function authorize(): bool
    {
        $listing = $this->route('listing');

        return $listing ? $this->user()->can('update', $listing) : true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'title' => trim((string) $this->input('title')),
            'description' => $this->filled('description') ? trim((string) $this->input('description')) : null,
            'city' => trim((string) $this->input('city')),
            'state' => strtoupper(trim((string) $this->input('state'))),
            'negotiable' => $this->boolean('negotiable'),
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'between:0,9999999999.99'],
            'unit' => ['required', 'in:'.implode(',', Listing::UNITS)],
            'quantity' => ['nullable', 'numeric', 'between:0,9999999999.99'],
            'negotiable' => ['boolean'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'regex:/^[A-Z]{2}$/'],
            'latitude' => ['nullable', 'required_with:longitude', 'numeric', 'between:-90,90'],
            'longitude' => ['nullable', 'required_with:latitude', 'numeric', 'between:-180,180'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }

    public function messages(): array
    {
        return [
            'state.regex' => 'Informe uma sigla de estado válida com duas letras.',
            'price.between' => 'O preço precisa estar entre R$ 0,00 e R$ 9.999.999.999,99.',
            'quantity.between' => 'A quantidade informada é muito alta.',
            'latitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
            'longitude.required_with' => 'Latitude e longitude precisam ser informadas juntas.',
        ];
    }
}
