<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class RejectProductRequest extends FormRequest
{
    public function authorize(): bool
    {
        // Chỉ cho phép teacher
        return $this->user()->role === 'teacher';
    }

    public function rules(): array
    {
        return [
            'product_id' => 'required|integer',
            'feedback' => 'required|string|min:5|max:1000',
        ];
    }

    public function messages(): array
    {
        return [
            'feedback.required' => 'Vui lòng nhập lý do từ chối.',
            'feedback.string' => 'Lý do từ chối phải là chuỗi.',
            'feedback.min' => 'Lý do quá ngắn.',
            'feedback.max' => 'Lý do quá dài.',
            'product_id.required' => 'Thiếu thông tin sản phẩm.',
            'product_id.integer' => 'Sản phẩm không hợp lệ.',
        ];
    }
}
