<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProductRequest extends FormRequest
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
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */

    public function rules(): array
    {
        $allRules = [
            'title'         => 'required|string|min:5|max:255',
            'description'   => 'nullable|string|max:2000',
            'thumbnail'     => 'nullable|url|max:255',
            'github_link'   => 'nullable|url|max:500',
            'demo_link'     => 'nullable|url|max:500',
            'awards'        => 'nullable|string|max:255',
            'status'        => 'required|in:pending,approved,rejected',
            'user_id'       => 'required|string|min:3|max:15|exists:users,user_id',
            'major_id'      => 'required|integer|exists:majors,major_id',
            'cate_id'       => 'required|integer|exists:categories,cate_id',
            'approved_by'   => 'nullable|string|max:15|exists:users,user_id',
            'submitted_at'  => 'nullable|date',
            'approved_at'   => 'nullable|date',
        ];

        // Lấy chỉ những trường xuất hiện trong request
        return array_intersect_key($allRules, $this->all());
    }

    public function messages(): array
    {
        return [
            'title.min' => 'Tiêu đề phải ít nhất 5 ký tự.',
            'title.max' => 'Tiêu đề không được vượt quá 255 ký tự.',
            'description.max' => 'Mô tả không được vượt quá 2000 ký tự.',
            'user_id.min' => 'User ID không hợp lệ.',
        ];
    }
}
