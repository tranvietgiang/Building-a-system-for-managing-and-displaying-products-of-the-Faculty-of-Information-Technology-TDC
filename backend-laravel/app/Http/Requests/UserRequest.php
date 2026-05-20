<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UserRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Columns NOT NULL: user_id, name, email, password, role
        $allRules = [
            'user_id' => 'required|string|min:3|max:15|unique:users,user_id',
            'name' => 'required|string|min:3|max:100',
            'email' => 'required|email|min:5|max:100|unique:users,email',
            'password' => 'required|string|min:6|max:255',
            'role' => 'required|in:student,teacher,admin',
            'phone' => 'nullable|string|max:20',
            'address' => 'nullable|string|max:200',
            'bio' => 'nullable|string|max:500',
            'mssv' => 'nullable|string|max:20',
            'class_name' => 'nullable|string|max:50',
            'major_id' => 'nullable|integer|exists:majors,major_id',
            'avatar' => 'nullable|url|max:255',
        ];

        return array_intersect_key($allRules, $this->all());
    }

    public function messages(): array
    {
        return [
            'user_id.min' => 'User ID phải ít nhất 3 ký tự.',
            'user_id.max' => 'User ID không được vượt quá 15 ký tự.',
            'name.min' => 'Tên phải ít nhất 3 ký tự.',
            'name.max' => 'Tên không được vượt quá 100 ký tự.',
            'email.min' => 'Email phải ít nhất 5 ký tự.',
            'email.max' => 'Email không được vượt quá 100 ký tự.',
            'password.min' => 'Mật khẩu phải ít nhất 6 ký tự.',
            'password.max' => 'Mật khẩu không được vượt quá 255 ký tự.',
        ];
    }
}
