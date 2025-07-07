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
        return auth()->check();
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array|string>
     */
    public function rules(): array
    {
        return [
            'products' => ['required', 'array', 'min:1', 'max:50'],
            'products.*.product_id' => [
                'required',
                'integer',
                'exists:products,id',
                'distinct',
            ],
            'products.*.quantity' => [
                'required',
                'integer',
                'min:1',
                'max:1000',
            ],
            'status' => [
                'sometimes',
                'string',
                'max:20',
                Rule::in(['pending', 'processing', 'completed', 'cancelled']),
            ],
            'notes' => [
                'sometimes',
                'nullable',
                'string',
                'max:1000',
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
            'products.required' => 'At least one product is required',
            'products.array' => 'Products must be provided as an array',
            'products.min' => 'At least one product is required',
            'products.max' => 'Order cannot contain more than 50 products',
            'products.*.product_id.required' => 'Product ID is required',
            'products.*.product_id.exists' => 'Selected product does not exist',
            'products.*.product_id.distinct' => 'Duplicate products are not allowed',
            'products.*.quantity.required' => 'Product quantity is required',
            'products.*.quantity.min' => 'Quantity must be at least 1',
            'products.*.quantity.max' => 'Quantity cannot exceed 1000 units',
            'status.in' => 'Invalid order status',
            'status.max' => 'Status cannot exceed 20 characters',
            'notes.max' => 'Notes cannot exceed 1000 characters',
            'notes.regex' => 'Notes contain invalid characters',
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

        if ($this->has('notes')) {
            $this->merge([
                'notes' => trim($this->input('notes'))
            ]);
        }
    }
}
