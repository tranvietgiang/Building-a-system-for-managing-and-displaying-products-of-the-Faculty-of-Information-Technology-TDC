<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Major;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherEndpointEdgeTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $student;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Major::query()->create([
            'major_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'major_code' => 'CNTT',
        ]);

        Category::query()->create([
            'cate_id' => 1,
            'category_name' => 'Web App',
        ]);

        $this->teacher = User::query()->create([
            'user_id' => 'teacher01',
            'name' => 'Teacher',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'major_id' => 1,
        ]);

        $this->student = User::query()->create([
            'user_id' => 'student01',
            'name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'major_id' => 1,
        ]);

        Product::query()->create([
            'title' => 'Test Product',
            'description' => 'This is a test product description',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
    }

    public function test_teacher_statistic_without_auth(): void
    {
        $response = $this->getJson('/api/v1/teacher/statistic');
        $response->assertStatus(401);
    }

    public function test_student_cannot_access_teacher_endpoints(): void
    {
        $response = $this->actingAs($this->student)
            ->getJson('/api/v1/teacher/statistic');
        $response->assertOk();
    }

    public function test_teacher_approve_non_existent_product(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/99999/approve');
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không tồn tại!');
    }

    public function test_teacher_approve_already_approved_product(): void
    {
        $product = Product::query()->first();
        $product->update(['status' => 'approved', 'approved_by' => 'teacher01']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$product->product_id}/approve");
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không chờ duyệt!');
    }

    public function test_teacher_approve_already_rejected_product(): void
    {
        $product = Product::query()->first();
        $product->update(['status' => 'rejected']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$product->product_id}/approve");
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không chờ duyệt!');
    }

    public function test_teacher_reject_without_feedback(): void
    {
        $product = Product::query()->first();
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $product->product_id,
            ]);

        $response->assertStatus(422);
    }

    public function test_teacher_reject_with_empty_feedback_fails_validation(): void
    {
        $product = Product::query()->first();
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $product->product_id,
                'feedback' => '',
            ]);

        $response->assertStatus(422);
        $response->assertJsonPath('message', 'Vui lòng nhập lý do từ chối.');
    }

    public function test_teacher_add_review_non_existent_product(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/99999/reviews', [
                'comment' => 'Good product!',
            ]);

        $response->assertStatus(404);
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không tồn tại.');
    }

    public function test_teacher_add_review_without_comment(): void
    {
        $product = Product::query()->first();
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$product->product_id}/reviews", []);

        $response->assertStatus(422);
    }

    public function test_student_cannot_review_product(): void
    {
        $product = Product::query()->first();
        $response = $this->actingAs($this->student)
            ->postJson("/api/v1/teacher/product/{$product->product_id}/reviews", [
                'comment' => 'Nice!',
            ]);

        $response->assertStatus(403);
    }

    public function test_teacher_cannot_review_other_major_product(): void
    {
        Major::query()->create([
            'major_id' => 2,
            'major_name' => 'Trí tuệ nhân tạo',
            'major_code' => 'AI',
        ]);

        Category::query()->create([
            'cate_id' => 2,
            'category_name' => 'AI App',
        ]);

        $otherProduct = Product::query()->create([
            'title' => 'AI Product',
            'description' => 'AI product description',
            'thumbnail' => 'http://example.com/ai.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 2,
            'cate_id' => 2,
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$otherProduct->product_id}/reviews", [
                'comment' => 'Nice AI product!',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Bạn không có quyền nhận xét sản phẩm thuộc chuyên ngành khác.');
    }

    public function test_teacher_data_invalid_status(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher?status=invalid_status');

        $response->assertStatus(422);
    }

    public function test_teacher_data_invalid_per_page(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher?per_page=abc');

        $response->assertStatus(422);
    }
}
