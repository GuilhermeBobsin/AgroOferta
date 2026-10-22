<?php

namespace App\Http\Requests;

use App\Models\Listing;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingIndexRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['nullable', 'string', 'max:120'],
            'category' => ['nullable', 'integer', 'exists:categories,id'],
            'unit' => ['nullable', Rule::in(Listing::UNITS)],
            'state' => ['nullable', 'string', 'size:2'],
            'price_min' => ['nullable', 'numeric', 'min:0'],
            'price_max' => ['nullable', 'numeric', 'min:0'],
            'sort' => ['nullable', Rule::in(['recent', 'price_asc', 'price_desc', 'nearest'])],
            'distance_km' => ['nullable', 'integer', Rule::in(Listing::SEARCH_RADIUS_OPTIONS)],
        ];
    }
}
