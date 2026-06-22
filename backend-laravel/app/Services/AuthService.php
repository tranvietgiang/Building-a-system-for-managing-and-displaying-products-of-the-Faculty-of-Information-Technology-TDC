<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\RefreshToken;
use App\Models\User;
use App\Repositories\UserRepository;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Illuminate\Support\Str;

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
        $tokens = $this->issueTokenPair($user);

        ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'login',
            'ip_address' => request()->ip(),
        ]);

        return [
            'success' => true,
            'user' => $user,
            ...$tokens,
        ];
    }

    public function refresh(string $plainTextRefreshToken): ?array
    {
        return DB::transaction(function () use ($plainTextRefreshToken) {
            $refreshToken = RefreshToken::query()
                ->where('token_hash', hash('sha256', $plainTextRefreshToken))
                ->lockForUpdate()
                ->first();

            if (!$refreshToken || $refreshToken->revoked_at || $refreshToken->expires_at->isPast()) {
                return null;
            }

            $user = $refreshToken->user;
            $refreshToken->delete();

            return $user ? $this->issueTokenPair($user) : null;
        });
    }

    public function revokeRefreshToken(?string $plainTextRefreshToken, string $userId): void
    {
        if (!$plainTextRefreshToken) {
            return;
        }

        RefreshToken::query()
            ->where('user_id', $userId)
            ->where('token_hash', hash('sha256', $plainTextRefreshToken))
            ->whereNull('revoked_at')
            ->update(['revoked_at' => now()]);
    }

    private function issueTokenPair(User $user): array
    {
        $accessTtl = max(1, (int) config('auth_tokens.access_token_ttl_minutes', 15));
        $refreshTtl = max(1, (int) config('auth_tokens.refresh_token_ttl_days', 30));
        $accessToken = $user->createToken(
            'access_token',
            ['*'],
            now()->addMinutes($accessTtl)
        )->plainTextToken;
        $plainTextRefreshToken = Str::random(80);

        $user->refreshTokens()->create([
            'token_hash' => hash('sha256', $plainTextRefreshToken),
            'expires_at' => now()->addDays($refreshTtl),
        ]);

        return [
            'token' => $accessToken,
            'access_token' => $accessToken,
            'refresh_token' => $plainTextRefreshToken,
            'token_type' => 'Bearer',
            'expires_in' => $accessTtl * 60,
        ];
    }
}
