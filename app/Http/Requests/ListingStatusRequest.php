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
            // A escolha explícita impede que uma venda seja marcada sem identificar onde ocorreu.
            'buyer_id' => [
                Rule::requiredIf($this->input('status') === ListingStatus::Sold->value),
                Rule::when(
                    $this->input('buyer_id') === 'outside',
                    ['in:outside'],
                    ['nullable', Rule::exists('negotiations', 'buyer_id')->where('listing_id', $this->route('listing')->id)],
                ),
            ],
            'sale_type' => [
                Rule::requiredIf($this->input('status') === ListingStatus::Sold->value),
                'nullable',
                Rule::in(['all', 'quantity']),
            ],
            'sold_quantity' => [
                Rule::requiredIf(
                    $this->input('status') === ListingStatus::Sold->value
                    && $this->input('sale_type') === 'quantity'
                ),
                'nullable',
                'numeric',
                'gt:0',
                function (string $attribute, mixed $value, \Closure $fail): void {
                    $listing = $this->route('listing');

                    if ($this->input('sale_type') !== 'quantity') {
                        return;
                    }

                    if ($listing->quantity === null) {
                        $fail('Informe a quantidade disponível no anúncio antes de registrar uma venda parcial.');
                    } elseif ((float) $value > (float) $listing->quantity) {
                        $fail('A quantidade vendida não pode ser maior que a disponível.');
                    }
                },
            ],
        ];
    }
}
