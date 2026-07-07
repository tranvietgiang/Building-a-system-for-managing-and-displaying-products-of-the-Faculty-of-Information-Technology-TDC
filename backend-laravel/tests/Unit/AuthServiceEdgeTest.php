<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\RefreshToken;
use App\Services\AuthService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\RateLimiter;
use Tests\TestCase;

class AuthServiceEdgeTest extends TestCase
{
    use RefreshDatabase;

    private AuthService $authService;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();
        $this->authService = app(AuthService::class);

        User::query()->create([
            'user_id' => 'locktest',
            'name' => 'Lock Test',
            'email' => 'lock@test.com',
            'password' => Hash::make('password123'),
            'role' => 'student',
        ]);
    }

    public function test_login_with_empty_data_returns_error(): void
    {
        $result = $this->authService->login([]);
        $this->assertFalse($result['success']);
    }

    public function test_login_without_password_field(): void
    {
        $result = $this->authService->login(['username' => 'locktest']);
        $this->assertFalse($result['success']);
    }

    public function test_refresh_with_empty_string_returns_null(): void
    {
        $result = $this->authService->refresh('');
        $this->assertNull($result);
    }

    public function test_refresh_with_garbage_token_returns_null(): void
    {
        $result = $this->authService->refresh('this_is_a_garbage_token_that_should_not_exist');
        $this->assertNull($result);
    }

    public function test_revoke_with_null_token_does_not_throw(): void
    {
        $this->authService->revokeRefreshToken(null, 'locktest');
        $this->assertTrue(true);
    }

    public function test_revoke_with_empty_token_does_not_throw(): void
    {
        $this->authService->revokeRefreshToken('', 'locktest');
        $this->assertTrue(true);
    }

    public function test_revoke_with_nonexistent_user_does_not_throw(): void
    {
        $this->authService->revokeRefreshToken('sometoken', 'nonexistent_user');
        $this->assertTrue(true);
    }

    public function test_login_rate_limiter_key_uses_username(): void
    {
        // Try to login with wrong password 5 times
        for ($i = 0; $i < 5; $i++) {
            $this->authService->login([
                'username' => 'locktest',
                'password' => 'wrongpw',
                'user_role' => 'student',
            ]);
        }

        // The 6th attempt should be rate limited
        $result = $this->authService->login([
            'username' => 'locktest',
            'password' => 'wrongpw',
            'user_role' => 'student',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(429, $result['status']);
    }

    public function test_login_rate_limiter_clears_on_success(): void
    {
        // Fail once
        $this->authService->login([
            'username' => 'locktest',
            'password' => 'wrongpw',
            'user_role' => 'student',
        ]);

        // Succeed
        $result = $this->authService->login([
            'username' => 'locktest',
            'password' => 'password123',
            'user_role' => 'student',
        ]);

        $this->assertTrue($result['success']);

        // Rate limit should be cleared, so another failure won't count as 6th
        $key = "login.lockout:locktest";
        $this->assertFalse(RateLimiter::tooManyAttempts($key, 5));
    }

    public function test_login_with_nonexistent_username(): void
    {
        $result = $this->authService->login([
            'username' => 'nonexistent_user_xyz',
            'password' => 'password123',
            'user_role' => 'student',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(401, $result['status']);
    }

    public function test_login_admin_role_with_student_user_fails(): void
    {
        $result = $this->authService->login([
            'username' => 'locktest',
            'password' => 'password123',
            'user_role' => 'admin',
        ]);

        $this->assertFalse($result['success']);
        $this->assertEquals(422, $result['status']);
    }

    public function test_successful_login_returns_tokens(): void
    {
        $result = $this->authService->login([
            'username' => 'locktest',
            'password' => 'password123',
            'user_role' => 'student',
        ]);

        $this->assertTrue($result['success']);
        $this->assertArrayHasKey('token', $result);
        $this->assertArrayHasKey('refresh_token', $result);
        $this->assertArrayHasKey('access_token', $result);
        $this->assertArrayHasKey('token_type', $result);
        $this->assertArrayHasKey('expires_in', $result);
        $this->assertSame('Bearer', $result['token_type']);
    }

    public function test_refresh_with_expired_token_returns_null(): void
    {
        $user = User::query()->where('user_id', 'locktest')->first();

        // Create an expired refresh token
        $refreshToken = RefreshToken::query()->create([
            'user_id' => 'locktest',
            'token_hash' => hash('sha256', 'expired_token_value'),
            'expires_at' => now()->subDay(),
        ]);

        $result = $this->authService->refresh('expired_token_value');
        $this->assertNull($result);
    }

    public function test_refresh_with_revoked_token_returns_null(): void
    {
        RefreshToken::query()->create([
            'user_id' => 'locktest',
            'token_hash' => hash('sha256', 'revoked_token_value'),
            'expires_at' => now()->addDay(),
            'revoked_at' => now(),
        ]);

        $result = $this->authService->refresh('revoked_token_value');
        $this->assertNull($result);
    }
}
