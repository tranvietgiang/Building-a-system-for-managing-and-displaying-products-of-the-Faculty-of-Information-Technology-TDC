<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Major;
use App\Models\Category;
use App\Models\Product;
use App\Models\Review;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherApprovalFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $teacher;
    private User $teacherAI;
    private User $student;
    private Product $pendingProduct;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed();

        Major::query()->create([
            'major_id' => 1,
            'major_name' => 'Công nghệ thông tin',
            'major_code' => 'CNTT',
        ]);

        Major::query()->create([
            'major_id' => 2,
            'major_name' => 'Trí tuệ nhân tạo',
            'major_code' => 'AI',
        ]);

        Category::query()->create([
            'cate_id' => 1,
            'category_name' => 'Web App',
        ]);

        $this->teacher = User::query()->create([
            'user_id' => 'teacher01',
            'name' => 'Teacher CNTT',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'major_id' => 1,
        ]);

        $this->teacherAI = User::query()->create([
            'user_id' => 'teacherAI',
            'name' => 'Teacher AI',
            'email' => 'teacher.ai@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'major_id' => 2,
        ]);

        $this->student = User::query()->create([
            'user_id' => 'student01',
            'name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'major_id' => 1,
        ]);

        $this->pendingProduct = Product::query()->create([
            'title' => 'Pending Product',
            'description' => 'This product is pending approval for testing',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
    }

    /* ─── Approval endpoints (require role:teacher,admin) ─── */

    public function test_approve_without_auth_returns_401(): void
    {
        $response = $this->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertStatus(401);
    }

    public function test_student_cannot_approve_product(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertStatus(403);
    }

    public function test_teacher_can_approve_pending_product(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertOk();
        $response->assertJsonPath('result', true);

        $this->assertEquals('approved', $this->pendingProduct->fresh()->status);
        $this->assertEquals('teacher01', $this->pendingProduct->fresh()->approved_by);
    }

    public function test_teacher_cannot_approve_already_approved_product(): void
    {
        $this->pendingProduct->update([
            'status' => 'approved',
            'approved_by' => 'teacher01',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không chờ duyệt!');
    }

    public function test_teacher_cannot_approve_rejected_product(): void
    {
        $this->pendingProduct->update(['status' => 'rejected']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không chờ duyệt!');
    }

    public function test_teacher_cannot_approve_other_major_product(): void
    {
        $response = $this->actingAs($this->teacherAI)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không tồn tại!');
    }

    /* ─── Rejection endpoints ─── */

    public function test_reject_without_auth_returns_401(): void
    {
        $response = $this->postJson('/api/v1/teacher/product/reject', [
            'product_id' => $this->pendingProduct->product_id,
            'feedback' => 'Cần cải thiện chất lượng',
        ]);
        $response->assertStatus(401);
    }

    public function test_student_cannot_reject_product(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $this->pendingProduct->product_id,
                'feedback' => 'Cần cải thiện chất lượng',
            ]);
        $response->assertStatus(403);
    }

    public function test_teacher_can_reject_pending_product(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $this->pendingProduct->product_id,
                'feedback' => 'Sản phẩm cần cải thiện về chất lượng nội dung',
            ]);
        $response->assertOk();
        $response->assertJsonPath('result', true);

        $this->assertEquals('rejected', $this->pendingProduct->fresh()->status);
    }

    public function test_reject_with_short_feedback_fails(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $this->pendingProduct->product_id,
                'feedback' => 'abc',
            ]);
        $response->assertStatus(422);
    }

    public function test_reject_without_feedback_fails(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $this->pendingProduct->product_id,
            ]);
        $response->assertStatus(422);
    }

    public function test_reject_non_existent_product_fails(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => 99999,
                'feedback' => 'Sản phẩm cần cải thiện về chất lượng nội dung',
            ]);
        $response->assertStatus(404);
    }

    public function test_reject_already_rejected_product_fails(): void
    {
        $this->pendingProduct->update(['status' => 'rejected']);

        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/reject', [
                'product_id' => $this->pendingProduct->product_id,
                'feedback' => 'Sản phẩm cần cải thiện về chất lượng nội dung',
            ]);
        $response->assertJsonPath('result', false);
        $response->assertJsonPath('message', 'Sản phẩm không chờ duyệt!');
    }

    /* ─── Review endpoints ─── */

    public function test_add_review_without_auth_returns_401(): void
    {
        $response = $this->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/reviews", [
            'comment' => 'Good product!',
        ]);
        $response->assertStatus(401);
    }

    public function test_student_cannot_add_review(): void
    {
        $response = $this->actingAs($this->student)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/reviews", [
                'comment' => 'Good product!',
            ]);
        $response->assertStatus(403);
    }

    public function test_teacher_can_add_review(): void
    {
        $product = $this->pendingProduct;

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$product->product_id}/reviews", [
                'comment' => 'Sản phẩm tốt, cần bổ sung thêm tài liệu',
            ]);
        $response->assertStatus(201);
        $response->assertJsonPath('result', true);

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->product_id,
            'teacher_id' => 'teacher01',
            'comment' => 'Sản phẩm tốt, cần bổ sung thêm tài liệu',
        ]);
    }

    public function test_add_review_without_comment_fails(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/reviews", []);
        $response->assertStatus(422);
    }

    public function test_teacher_cannot_review_other_major_product(): void
    {
        $otherProduct = Product::query()->create([
            'title' => 'AI Product',
            'description' => 'AI product description',
            'thumbnail' => 'http://example.com/ai.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 2,
            'cate_id' => 1,
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$otherProduct->product_id}/reviews", [
                'comment' => 'Nice AI product!',
            ]);

        $response->assertStatus(403);
        $response->assertJsonPath('message', 'Bạn không có quyền nhận xét sản phẩm thuộc chuyên ngành khác.');
    }

    public function test_add_review_to_approved_product_succeeds(): void
    {
        $this->pendingProduct->update([
            'status' => 'approved',
            'approved_by' => 'teacher01',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/reviews", [
                'comment' => 'Review after approval',
            ]);
        $response->assertStatus(201);
    }

    public function test_add_review_to_rejected_product_succeeds(): void
    {
        $this->pendingProduct->update(['status' => 'rejected']);

        $response = $this->actingAs($this->teacher)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/reviews", [
                'comment' => 'Review after rejection',
            ]);
        $response->assertStatus(201);
    }

    public function test_add_review_non_existent_product_fails(): void
    {
        $response = $this->actingAs($this->teacher)
            ->postJson('/api/v1/teacher/product/99999/reviews', [
                'comment' => 'Good product!',
            ]);
        $response->assertStatus(404);
        $response->assertJsonPath('result', false);
    }

    /* ─── Teacher data endpoints (auth required, no role gate) ─── */

    public function test_get_teacher_statistic_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/v1/teacher/statistic');
        $response->assertStatus(401);
    }

    public function test_get_teacher_data_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/v1/teacher');
        $response->assertStatus(401);
    }

    public function test_get_teacher_data_with_valid_status(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher?status=pending');
        $response->assertOk();
        $response->assertJsonStructure([
            'data' => [
                'products',
                'counts',
            ],
            'teacher_data_result',
        ]);
    }

    public function test_get_teacher_data_with_all_status(): void
    {
        $statuses = ['pending', 'approved', 'rejected'];
        foreach ($statuses as $status) {
            $response = $this->actingAs($this->teacher)
                ->getJson("/api/v1/teacher?status={$status}");
            $response->assertOk();
        }
    }

    public function test_get_teacher_data_invalid_status_returns_422(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher?status=invalid');
        $response->assertStatus(422);
    }

    public function test_get_teacher_data_invalid_per_page_returns_422(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher?per_page=abc');
        $response->assertStatus(422);
    }

    public function test_get_teacher_data_with_per_page(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher?per_page=10');
        $response->assertOk();
    }

    /* ─── Teacher with admin role can also approve ─── */

    public function test_admin_with_major_can_approve_product(): void
    {
        $admin = User::query()->create([
            'user_id' => 'admin_with_major',
            'name' => 'Admin With Major',
            'email' => 'adminwm@test.com',
            'password' => bcrypt('password'),
            'role' => 'admin',
            'major_id' => 1,
        ]);

        $response = $this->actingAs($admin)
            ->postJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}/approve");
        $response->assertOk();
        $response->assertJsonPath('result', true);
    }

    /* ─── Teacher statistic ─── */

    public function test_teacher_statistic_with_data(): void
    {
        $this->pendingProduct->update([
            'status' => 'approved',
            'approved_by' => 'teacher01',
            'approved_at' => now(),
        ]);

        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher/statistic');
        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'teacher_result',
        ]);
    }

    /* ─── Product detail for teacher ─── */

    public function test_teacher_can_view_product_detail(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}");
        $response->assertOk();
    }

    public function test_teacher_cannot_view_other_major_product_detail(): void
    {
        $otherProduct = Product::query()->create([
            'title' => 'AI Product',
            'description' => 'AI product description',
            'thumbnail' => 'http://example.com/ai.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 2,
            'cate_id' => 1,
        ]);

        $response = $this->actingAs($this->teacher)
            ->getJson("/api/v1/teacher/product/{$otherProduct->product_id}");
        $response->assertStatus(404);
    }

    public function test_view_non_existent_product_detail_returns_422(): void
    {
        $response = $this->actingAs($this->teacher)
            ->getJson('/api/v1/teacher/product/99999');
        $response->assertStatus(422);
    }

    public function test_view_product_detail_without_auth_returns_401(): void
    {
        $response = $this->getJson("/api/v1/teacher/product/{$this->pendingProduct->product_id}");
        $response->assertStatus(401);
    }
}
