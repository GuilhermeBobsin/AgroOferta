<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class MessageRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()->can('message', $this->route('negotiation'));
    }

    public function rules(): array
    {
        return ['body' => ['required', 'string', 'max:2000']];
    }
}
