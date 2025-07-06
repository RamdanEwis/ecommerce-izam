<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateOrderRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => ['required', 'string', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
            'products' => ['sometimes', 'array', 'min:1'],
            'products.*.product_id' => [
                Rule::requiredIf(fn() => $this->has('products')),
                'integer',
                'exists:products,id'
            ],
            'products.*.quantity' => [
                Rule::requiredIf(fn() => $this->has('products')),
                'integer',
                'min:1'
            ],
        ];
    }

    /**
     * Get custom error messages for validation rules.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'status.required' => 'Order status is required',
            'status.in' => 'Invalid order status',
            'products.array' => 'Products must be provided as an array',
            'products.min' => 'At least one product is required',
            'products.*.product_id.required' => 'Product ID is required',
            'products.*.product_id.exists' => 'Selected product does not exist',
            'products.*.quantity.required' => 'Product quantity is required',
            'products.*.quantity.min' => 'Quantity must be at least 1',
        ];
    }
}
