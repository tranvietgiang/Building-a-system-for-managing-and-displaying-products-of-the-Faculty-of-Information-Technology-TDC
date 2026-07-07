<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Major;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class UploadFeatureTest extends TestCase
{
    use RefreshDatabase;

    private User $student;
    private User $teacher;

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

        Major::query()->create([
            'major_id' => 3,
            'major_name' => 'Mạng máy tính',
            'major_code' => 'MMT',
        ]);

        Major::query()->create([
            'major_id' => 4,
            'major_name' => 'Thiết kế đồ họa',
            'major_code' => 'TKDH',
        ]);

        Category::query()->create([
            'cate_id' => 1,
            'category_name' => 'Web App',
        ]);

        Category::query()->create([
            'cate_id' => 2,
            'category_name' => 'Mobile App',
        ]);

        $this->student = User::query()->create([
            'user_id' => 'student01',
            'name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'major_id' => 1,
        ]);

        $this->teacher = User::query()->create([
            'user_id' => 'teacher01',
            'name' => 'Teacher',
            'email' => 'teacher@test.com',
            'password' => bcrypt('password'),
            'role' => 'teacher',
            'major_id' => 1,
        ]);
    }

    private function actingAsStudent(): static
    {
        return $this->actingAs($this->student);
    }

    /* ─── Upload endpoint ─── */

    public function test_upload_without_auth_returns_401(): void
    {
        $response = $this->postJson('/api/v1/upload', []);
        $response->assertStatus(401);
    }

    public function test_upload_with_empty_data_fails_validation(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', []);
        $response->assertStatus(422);
    }

    public function test_upload_without_title_fails_validation(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_without_major_code_fails_validation(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Test Product Title',
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_with_short_title_fails_validation(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'ABC',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_with_invalid_cate_id_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Valid Product Title',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 999,
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_without_images_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Valid Product Title',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_with_invalid_image_type_fails(): void
    {
        $file = UploadedFile::fake()->create('document.pdf', 100);

        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Valid Product Title',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
            'images' => [$file],
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_with_too_many_images_fails(): void
    {
        $images = [];
        for ($i = 0; $i < 15; $i++) {
            $images[] = UploadedFile::fake()->image("image{$i}.jpg", 100, 100);
        }

        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Valid Product Title',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
            'images' => $images,
        ]);

        $response->assertStatus(422);
    }

    public function test_teacher_cannot_upload_product(): void
    {
        $response = $this->actingAs($this->teacher)->postJson('/api/v1/upload', [
            'title' => 'Teacher Product',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
        $response->assertStatus(403);
    }

    public function test_upload_with_invalid_github_link_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Valid Product Title Here',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
            'github_link' => 'not-a-url',
            'images' => [UploadedFile::fake()->image('test.jpg', 100, 100)],
        ]);
        $response->assertStatus(422);
    }

    public function test_upload_cntt_product_missing_major_fields_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'Valid Product Title Here',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
            'images' => [UploadedFile::fake()->image('test.jpg', 100, 100)],
        ]);

        $response->assertStatus(422);
    }

    public function test_upload_ai_product_without_required_fields_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'AI Product Title',
            'major_code' => 'AI',
            'major_id' => 2,
            'cate_id' => 1,
            'images' => [UploadedFile::fake()->image('test.jpg', 100, 100)],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['model_used', 'framework', 'dataset_used']);
    }

    public function test_upload_cntt_product_without_required_fields_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'CNTT Product Title',
            'major_code' => 'CNTT',
            'major_id' => 1,
            'cate_id' => 1,
            'images' => [UploadedFile::fake()->image('test.jpg', 100, 100)],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['programming_language', 'framework', 'database_used']);
    }

    public function test_upload_mmt_product_without_required_fields_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'MMT Product Title',
            'major_code' => 'MMT',
            'major_id' => 3,
            'cate_id' => 1,
            'images' => [UploadedFile::fake()->image('test.jpg', 100, 100)],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['simulation_tool', 'topology_type']);
    }

    public function test_upload_tkdh_product_without_required_fields_fails(): void
    {
        $response = $this->actingAsStudent()->postJson('/api/v1/upload', [
            'title' => 'TKDH Product Title',
            'major_code' => 'TKDH',
            'major_id' => 4,
            'cate_id' => 1,
            'images' => [UploadedFile::fake()->image('test.jpg', 100, 100)],
        ]);

        $response->assertStatus(422);
        $response->assertJsonValidationErrors(['design_type']);
    }

    public function test_count_published_without_auth_returns_401(): void
    {
        $response = $this->getJson('/api/v1/upload/count-published');
        $response->assertStatus(401);
    }

    public function test_count_published_with_no_products_returns_valid(): void
    {
        $response = $this->actingAsStudent()->getJson('/api/v1/upload/count-published');
        $response->assertJsonStructure([
            'data',
            'uploadCount_result',
        ]);
    }

    /* ─── Product list endpoints ─── */

    public function test_student_can_view_own_products(): void
    {
        Product::query()->create([
            'title' => 'My Product',
            'description' => 'Description here',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $response = $this->actingAsStudent()->getJson('/api/v1/products');
        $response->assertOk();
        $response->assertJsonStructure([
            'data',
            'stats',
        ]);
    }

    public function test_student_can_filter_products_by_status(): void
    {
        Product::query()->create([
            'title' => 'Approved Product',
            'description' => 'Description here',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'approved',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $response = $this->actingAsStudent()->getJson('/api/v1/products?status=approved');
        $response->assertOk();
    }

    public function test_student_can_filter_with_invalid_status(): void
    {
        Product::query()->create([
            'title' => 'Test Product',
            'description' => 'Description here',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $response = $this->actingAsStudent()->getJson('/api/v1/products?status=invalid_status');
        $response->assertStatus(422);
    }

    /* ─── Delete product ─── */

    public function test_delete_own_product_succeeds(): void
    {
        $product = Product::query()->create([
            'title' => 'To Delete',
            'description' => 'Description here',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $response = $this->actingAsStudent()->deleteJson("/api/v1/student/product/{$product->product_id}");
        $response->assertOk();
        $response->assertJsonPath('deleted', true);
    }

    public function test_delete_other_student_product_fails(): void
    {
        $otherStudent = User::query()->create([
            'user_id' => 'student02',
            'name' => 'Other Student',
            'email' => 'other@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'major_id' => 1,
        ]);

        $product = Product::query()->create([
            'title' => 'Other Product',
            'description' => 'Description here',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student02',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $response = $this->actingAsStudent()->deleteJson("/api/v1/student/product/{$product->product_id}");
        $response->assertStatus(404);
    }

    public function test_delete_non_existent_product_fails_validation(): void
    {
        $response = $this->actingAsStudent()->deleteJson('/api/v1/student/product/99999');
        $response->assertStatus(422);
    }

    public function test_delete_without_auth_returns_401(): void
    {
        $product = Product::query()->create([
            'title' => 'Test',
            'description' => 'Description here',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $response = $this->deleteJson("/api/v1/student/product/{$product->product_id}");
        $response->assertStatus(401);
    }

    /* ─── Product Search (student) ─── */

    public function test_search_products_without_query_succeeds(): void
    {
        $response = $this->actingAsStudent()->getJson('/api/v1/products/search');
        $response->assertOk();
    }

    public function test_search_products_with_special_chars_fails(): void
    {
        $response = $this->actingAsStudent()->getJson('/api/v1/products/search?q=' . urlencode('<script>alert("xss")</script>'));
        $response->assertStatus(422);
    }
}
