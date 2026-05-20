<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductViewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true; // hoặc check role teacher
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer|exists:products,product_id',
        ];
    }

    public function validationData()
    {
        return array_merge($this->all(), [
            'product_id' => $this->route('product_id') ?? $this->input('product_id'),
        ]);
    }
}
