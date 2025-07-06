<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class OrderProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'order_id' => 'integer',
            'product_id' => 'integer',
            'quantity' => 'integer',
            'price' => 'numeric',
        ];
    }
}
