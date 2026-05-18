<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class LoginRequest extends FormRequest
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
        return [
            'username' => 'required|string|min:3|max:100',
            'password' => 'required|string|min:6|max:255',
            'user_role' => 'required|string|in:student,lecturer,teacher,admin',
        ];
    }

    public function messages(): array
    {
        return [
            'username.min' => 'Tên đăng nhập phải ít nhất 3 ký tự.',
            'username.max' => 'Tên đăng nhập không được vượt quá 100 ký tự.',
            'password.min' => 'Mật khẩu phải ít nhất 6 ký tự.',
            'password.max' => 'Mật khẩu không được vượt quá 255 ký tự.',
        ];
    }
}
