<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class PurchaseRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            'card_number' => ['required', 'string'],
            'machine_id' => ['required', 'string'],
            'slot_number' => ['required', 'integer', 'min:1'],
            'product_price' => ['required', 'integer', 'min:1'],
        ];
    }
}
