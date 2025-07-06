<?php

namespace App\Http\Requests\Order;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreOrderRequest extends FormRequest
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
            'products' => ['required', 'array', 'min:1'],
            'products.*.product_id' => ['required', 'integer', 'exists:products,id'],
            'products.*.quantity' => ['required', 'integer', 'min:1'],
            'status' => ['sometimes', 'string', Rule::in(['pending', 'processing', 'completed', 'cancelled'])],
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
            'products.required' => 'At least one product is required',
            'products.array' => 'Products must be provided as an array',
            'products.min' => 'At least one product is required',
            'products.*.product_id.required' => 'Product ID is required',
            'products.*.product_id.exists' => 'Selected product does not exist',
            'products.*.quantity.required' => 'Product quantity is required',
            'products.*.quantity.min' => 'Quantity must be at least 1',
            'status.in' => 'Invalid order status',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        if ($this->user() && !$this->has('user_id')) {
            $this->merge([
                'user_id' => $this->user()->id
            ]);
        }
    }
}
