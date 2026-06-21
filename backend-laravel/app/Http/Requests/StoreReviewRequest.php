<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreReviewRequest extends FormRequest
{
    public function authorize(): bool
    {
        return in_array($this->user()?->role, ['teacher', 'admin'], true);
    }

    public function rules(): array
    {
        return [
            'comment' => ['required', 'string', 'max:2000'],
        ];
    }

    public function messages(): array
    {
        return [
            'comment.required' => 'Vui lòng nhập nhận xét.',
            'comment.max' => 'Nhận xét không được vượt quá 2000 ký tự.',
        ];
    }
}
