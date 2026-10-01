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
            'state' => strtoupper((string) $this->state),
            'negotiable' => $this->boolean('negotiable'),
        ]);
    }

    public function rules(): array
    {
        return [
            'category_id' => ['required', 'exists:categories,id'],
            'title' => ['required', 'string', 'max:120'],
            'description' => ['nullable', 'string', 'max:3000'],
            'price' => ['required', 'numeric', 'min:0'],
            'unit' => ['required', 'in:'.implode(',', Listing::UNITS)],
            'quantity' => ['nullable', 'numeric', 'min:0'],
            'negotiable' => ['boolean'],
            'city' => ['required', 'string', 'max:100'],
            'state' => ['required', 'string', 'size:2'],
            'image' => ['nullable', 'image', 'mimes:jpg,jpeg,png,webp', 'max:4096'],
        ];
    }
}
