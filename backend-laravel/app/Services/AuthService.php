<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;

class AuthService
{
    private const MAX_LOGIN_ATTEMPTS = 5;
    private const LOCKOUT_MINUTES = 15;

    public function __construct(protected UserRepository $userRepository) {}

    public function login(array $data)
    {
        $username = $data['username'];

        // Check if account is locked
        $lockoutKey = "login.lockout:{$username}";
        if (RateLimiter::tooManyAttempts($lockoutKey, self::MAX_LOGIN_ATTEMPTS)) {
            $seconds = RateLimiter::availableIn($lockoutKey);
            return [
                'success' => false,
                'message' => "Tài khoản bị khóa tạm thời. Vui lòng thử lại sau {$seconds} giây.",
                'status' => 429,
            ];
        }

        $user = $this->userRepository->findById($username);

        if (!$user || !hash_equals((string) $user->user_id, (string) $username)) {
            RateLimiter::hit($lockoutKey, self::LOCKOUT_MINUTES * 60);
            return [
                'success' => false,
                'message' => 'Sai tài khoản hoặc mật khẩu!',
                'status' => 401,
            ];
        }

        if (!Hash::check($data['password'], $user->password)) {
            RateLimiter::hit($lockoutKey, self::LOCKOUT_MINUTES * 60);
            return [
                'success' => false,
                'message' => 'Sai tài khoản hoặc mật khẩu!',
                'status' => 401,
            ];
        }

        // Kiểm tra role: frontend gửi "lecturer" nhưng backend lưu "teacher"
        $selectedRole = strtolower($data['user_role'] ?? '');
        $isSelectedLecturer = in_array($selectedRole, ['lecturer', 'teacher'], true);

        if ($selectedRole === 'student' && $user->role !== 'student' && $user->role !== 'admin') {
            RateLimiter::hit($lockoutKey, self::LOCKOUT_MINUTES * 60);
            return [
                'success' => false,
                'message' => 'Tài khoản này không phải sinh viên!',
                'status' => 422,
            ];
        }

        if ($isSelectedLecturer && $user->role !== 'teacher' && $user->role !== 'admin') {
            RateLimiter::hit($lockoutKey, self::LOCKOUT_MINUTES * 60);
            return [
                'success' => false,
                'message' => 'Tài khoản này không phải giảng viên!',
                'status' => 422,
            ];
        }

        if ($selectedRole === 'admin' && $user->role !== 'admin') {
            RateLimiter::hit($lockoutKey, self::LOCKOUT_MINUTES * 60);
            return [
                'success' => false,
                'message' => 'Tài khoản này không phải quản trị viên!',
                'status' => 422,
            ];
        }

        // Clear failed attempts on successful login
        RateLimiter::clear($lockoutKey);

        // tạo token
        $token = $user->createToken('auth_token')->plainTextToken;

        ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'login',
            'ip_address' => request()->ip(),
        ]);

        return [
            'success' => true,
            'user' => $user,
            'token' => $token
        ];
    }
}
