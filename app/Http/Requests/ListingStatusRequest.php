<?php

namespace App\Http\Requests;

use App\Enums\ListingStatus;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class ListingStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('update', $this->route('listing'));
    }

    public function rules(): array
    {
        return [
            'status' => ['required', Rule::enum(ListingStatus::class)],
            // Comprador precisa ter negociado este anúncio; vazio = vendido fora da plataforma
            'buyer_id' => ['nullable', Rule::exists('negotiations', 'buyer_id')
                ->where('listing_id', $this->route('listing')->id)],
        ];
    }
}
