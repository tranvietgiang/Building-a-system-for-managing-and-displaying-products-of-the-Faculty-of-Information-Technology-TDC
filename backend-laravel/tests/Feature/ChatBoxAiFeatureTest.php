<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Major;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChatBoxAiFeatureTest extends TestCase
{
    use RefreshDatabase;

    private array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('majors')->insert([
            ['major_id' => 1, 'major_name' => 'Công nghệ thông tin', 'major_code' => 'CNTT', 'description' => 'Ngành CNTT'],
            ['major_id' => 2, 'major_name' => 'Trí tuệ nhân tạo', 'major_code' => 'AI', 'description' => 'Ngành AI'],
        ]);

        User::query()->create([
            'user_id' => 'student01',
            'name' => 'Student CNTT',
            'email' => 'student@test.com',
            'password' => bcrypt('12345678'),
            'role' => 'student',
            'major_id' => 1,
        ]);

        User::query()->create([
            'user_id' => 'teacher01',
            'name' => 'Teacher CNTT',
            'email' => 'teacher@test.com',
            'password' => bcrypt('12345678'),
            'role' => 'teacher',
            'major_id' => 1,
        ]);

        User::query()->create([
            'user_id' => 'admin01',
            'name' => 'Admin',
            'email' => 'admin@test.com',
            'password' => bcrypt('12345678'),
            'role' => 'admin',
            'major_id' => null,
        ]);
    }

    private function loginAs(string $userId, string $role): string
    {
        if (isset($this->tokens[$userId])) {
            return $this->tokens[$userId];
        }

        $response = $this->postJson('/api/v1/login', [
            'username' => $userId,
            'password' => '12345678',
            'user_role' => $role,
        ]);

        $response->assertStatus(200);

        return $this->tokens[$userId] = $response->json('access_token');
    }

    private function assertChatResponseStructure($response): void
    {
        $response->assertStatus(200);
        $response->assertJsonStructure([
            'reply',
            'products',
        ]);
    }

    /* ─── Message validation ─── */

    public function test_empty_message_returns_422(): void
    {
        $response = $this->postJson('/api/v1/ai/send', ['message' => '']);
        $response->assertStatus(422);
    }

    public function test_too_short_message_returns_422(): void
    {
        $response = $this->postJson('/api/v1/ai/send', ['message' => 'ab']);
        $response->assertStatus(422);
    }

    public function test_too_long_message_returns_422(): void
    {
        $response = $this->postJson('/api/v1/ai/send', ['message' => str_repeat('a', 1001)]);
        $response->assertStatus(422);
    }

    public function test_message_with_xss_passes_validation_as_normal(): void
    {
        $response = $this->postJson('/api/v1/ai/send', ['message' => '<script>alert("xss")</script>']);
        $this->assertChatResponseStructure($response);
    }

    /* ─── Visitor (no auth) ─── */

    public function test_visitor_asks_about_products(): void
    {
        $response = $this->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem sản phẩm CNTT',
        ]);
        $this->assertChatResponseStructure($response);
    }

    public function test_visitor_asks_irrelevant_question(): void
    {
        $response = $this->postJson('/api/v1/ai/send', [
            'message' => 'Chào bạn, bạn khỏe không?',
        ]);
        $this->assertChatResponseStructure($response);
    }

    /* ─── Student (authenticated) ─── */

    public function test_student_asks_about_own_major(): void
    {
        $token = $this->loginAs('student01', 'student');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho em xem đồ án CNTT',
        ]);
        $this->assertChatResponseStructure($response);
    }

    public function test_student_without_major_gets_instructed(): void
    {
        $studentNoMajor = User::query()->create([
            'user_id' => 'student_no_major',
            'name' => 'Student No Major',
            'email' => 'nonmajor@test.com',
            'password' => bcrypt('12345678'),
            'role' => 'student',
            'major_id' => null,
        ]);

        $responseLogin = $this->postJson('/api/v1/login', [
            'username' => 'student_no_major',
            'password' => '12345678',
            'user_role' => 'student',
        ]);
        $token = $responseLogin->json('access_token');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho em xem đồ án',
        ]);
        $response->assertStatus(403);
        $this->assertStringContainsString('chưa được gán ngành học', $response->json('reply'));
    }

    /* ─── Teacher (authenticated) ─── */

    public function test_teacher_asks_about_own_major(): void
    {
        $token = $this->loginAs('teacher01', 'teacher');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho tôi xem đồ án CNTT',
        ]);
        $this->assertChatResponseStructure($response);
    }

    public function test_teacher_without_major_gets_instructed(): void
    {
        $teacherNoMajor = User::query()->create([
            'user_id' => 'teacher_no_major',
            'name' => 'Teacher No Major',
            'email' => 'nonmajor_teacher@test.com',
            'password' => bcrypt('12345678'),
            'role' => 'teacher',
            'major_id' => null,
        ]);

        $responseLogin = $this->postJson('/api/v1/login', [
            'username' => 'teacher_no_major',
            'password' => '12345678',
            'user_role' => 'lecturer',
        ]);
        $token = $responseLogin->json('access_token');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho tôi xem đồ án',
        ]);
        $response->assertStatus(403);
        $this->assertStringContainsString('chưa được gán ngành học', $response->json('reply'));
    }

    /* ─── Admin (authenticated) ─── */

    public function test_admin_asks_system_wide(): void
    {
        $token = $this->loginAs('admin01', 'admin');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Tổng quan hệ thống',
        ]);
        $this->assertChatResponseStructure($response);
    }

    public function test_admin_asks_about_majors(): void
    {
        $token = $this->loginAs('admin01', 'admin');

        $questions = [
            'Cho xem sản phẩm CNTT',
            'Sản phẩm AI',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);
            $this->assertChatResponseStructure($response);
        }
    }

    /* ─── Feature-related questions ─── */

    public function test_student_asks_upload_question(): void
    {
        $token = $this->loginAs('student01', 'student');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Làm sao để nộp đồ án?',
        ]);
        $this->assertChatResponseStructure($response);
    }

    public function test_teacher_asks_approve_question(): void
    {
        $token = $this->loginAs('teacher01', 'teacher');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Làm sao để duyệt đồ án?',
        ]);
        $this->assertChatResponseStructure($response);
    }

    /* ─── Special characters ─── */

    public function test_message_with_special_characters(): void
    {
        $response = $this->postJson('/api/v1/ai/send', ['message' => '!@#$%^&*()']);
        $this->assertChatResponseStructure($response);
    }

    /* ─── Number input ─── */

    public function test_message_with_numbers(): void
    {
        $response = $this->postJson('/api/v1/ai/send', ['message' => '1234567890']);
        $this->assertChatResponseStructure($response);
    }

    /* ─── Cross-major access (student) ─── */

    public function test_student_only_sees_own_major(): void
    {
        $token = $this->loginAs('student01', 'student');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem sản phẩm AI',
        ]);
        $this->assertChatResponseStructure($response);
        $this->assertStringContainsString('chỉ có thể xem', $response->json('reply'));
    }
}
