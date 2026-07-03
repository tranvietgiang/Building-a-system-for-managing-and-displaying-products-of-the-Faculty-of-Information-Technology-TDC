<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AuthEdgeTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        User::query()->create([
            'user_id' => 'testuser',
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => bcrypt('password123'),
            'role' => 'student',
        ]);
    }

    public function test_login_with_empty_username(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => '',
            'password' => 'password123',
            'user_role' => 'student',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_empty_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => '',
            'user_role' => 'student',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_invalid_role(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'password123',
            'user_role' => 'superadmin',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_without_role_field(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'password123',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_sql_injection_username(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => "' OR '1'='1",
            'password' => 'anything',
            'user_role' => 'student',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_sql_injection_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'nonexistent',
            'password' => "' OR '1'='1",
            'user_role' => 'student',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_xss_in_username(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => '<script>alert("xss")</script>',
            'password' => 'password123',
            'user_role' => 'student',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_with_extremely_long_username(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => str_repeat('a', 1000),
            'password' => 'password123',
            'user_role' => 'student',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_with_extremely_long_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => str_repeat('a', 1000),
            'user_role' => 'student',
        ]);

        $response->assertStatus(422);
    }

    public function test_login_wrong_password(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'wrongpassword',
            'user_role' => 'student',
        ]);

        $response->assertStatus(401);
    }

    public function test_login_rate_limiting(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'username' => 'testuser',
                'password' => 'wrongpassword',
                'user_role' => 'student',
            ]);

            if ($i >= 5) {
                $response->assertStatus(429);
            }
        }
    }

    public function test_login_with_lecturer_role_alias(): void
    {
        $teacher = User::query()->create([
            'user_id' => 'teacher01',
            'name' => 'Teacher One',
            'email' => 'teacher@example.com',
            'password' => bcrypt('password123'),
            'role' => 'teacher',
        ]);

        $response = $this->postJson('/api/v1/login', [
            'username' => 'teacher01',
            'password' => 'password123',
            'user_role' => 'lecturer',
        ]);

        $response->assertStatus(200);
        $response->assertJsonPath('success', true);
    }

    public function test_login_student_with_teacher_role_fails(): void
    {
        $response = $this->postJson('/api/v1/login', [
            'username' => 'testuser',
            'password' => 'password123',
            'user_role' => 'teacher',
        ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Tài khoản này không phải giảng viên!');
    }

    public function test_refresh_with_empty_token(): void
    {
        $response = $this->postJson('/api/v1/refresh', [
            'refresh_token' => '',
        ]);

        $response->assertStatus(422);
    }

    public function test_refresh_with_invalid_token(): void
    {
        $response = $this->postJson('/api/v1/refresh', [
            'refresh_token' => 'invalid_token_here',
        ]);

        $response->assertStatus(401);
    }

    public function test_logout_without_token(): void
    {
        $response = $this->postJson('/api/v1/logout');
        $response->assertStatus(401);
    }

    public function test_me_without_token(): void
    {
        $response = $this->getJson('/api/v1/me');
        $response->assertStatus(401);
    }
}
