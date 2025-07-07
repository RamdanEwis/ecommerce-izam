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
        return auth()->check() && $this->user()->can('update', $this->route('order'));
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'status' => [
                'required',
                'string',
                'max:20',
                Rule::in(['pending', 'processing', 'completed', 'cancelled']),
            ],
            'products' => [
                'sometimes',
                'array',
                'min:1',
                'max:50', // Limit max products
            ],
            'products.*.product_id' => [
                Rule::requiredIf(fn() => $this->has('products')),
                'integer',
                'exists:products,id',
                'distinct', // Prevent duplicate products
            ],
            'products.*.quantity' => [
                Rule::requiredIf(fn() => $this->has('products')),
                'integer',
                'min:1',
                'max:1000', // Reasonable maximum quantity
            ],
            // Additional validation for potential XSS/injection
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
                'regex:/^[\pL\pN\s\-\_\.\,\!\?]+$/u', // Allow only letters, numbers, basic punctuation
            ],
            'reason' => [
                'required_if:status,cancelled',
                'nullable',
                'string',
                'max:500',
                'regex:/^[\pL\pN\s\-\_\.\,\!\?]+$/u',
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
            'status.max' => 'Status cannot exceed 20 characters',
            'products.array' => 'Products must be provided as an array',
            'products.min' => 'At least one product is required',
            'products.max' => 'Order cannot contain more than 50 products',
            'products.*.product_id.required' => 'Product ID is required',
            'products.*.product_id.exists' => 'Selected product does not exist',
            'products.*.product_id.distinct' => 'Duplicate products are not allowed',
            'products.*.quantity.required' => 'Product quantity is required',
            'products.*.quantity.min' => 'Quantity must be at least 1',
            'products.*.quantity.max' => 'Quantity cannot exceed 1000 units',
            'notes.max' => 'Notes cannot exceed 1000 characters',
            'notes.regex' => 'Notes contain invalid characters',
            'reason.required_if' => 'Please provide a reason for cancelling the order',
            'reason.max' => 'Reason cannot exceed 500 characters',
            'reason.regex' => 'Reason contains invalid characters',
        ];
    }

    /**
     * Prepare the data for validation.
     */
    protected function prepareForValidation(): void
    {
        // Trim whitespace from string inputs
        foreach (['notes', 'reason'] as $field) {
            if ($this->has($field)) {
                $this->merge([
                    $field => trim($this->input($field))
                ]);
            }
        }
    }
}
