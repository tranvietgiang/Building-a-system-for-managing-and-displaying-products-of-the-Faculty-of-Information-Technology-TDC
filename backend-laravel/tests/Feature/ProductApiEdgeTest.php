<?php

namespace Tests\Feature;

use App\Models\User;
use App\Models\Major;
use App\Models\Category;
use App\Models\Product;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ProductApiEdgeTest extends TestCase
{
    use RefreshDatabase;

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

        $student = User::query()->create([
            'user_id' => 'student01',
            'name' => 'Student',
            'email' => 'student@test.com',
            'password' => bcrypt('password'),
            'role' => 'student',
            'major_id' => 1,
        ]);

        $product = Product::query()->create([
            'title' => 'Test Product',
            'description' => 'This is a test product description',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'approved',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);
    }

    public function test_get_product_needs_auth_returns_401(): void
    {
        // Sanctum middleware blocks unauthenticated requests before route logic
        $response = $this->getJson('/api/v1/product/1');
        $response->assertStatus(401);
    }

    public function test_get_product_with_zero_id_after_auth(): void
    {
        $student = User::query()->where('user_id', 'student01')->first();
        $response = $this->actingAs($student)->getJson('/api/v1/product/0');
        $response->assertStatus(404);
    }

    public function test_get_product_with_negative_id_after_auth(): void
    {
        $student = User::query()->where('user_id', 'student01')->first();
        $response = $this->actingAs($student)->getJson('/api/v1/product/-1');
        $response->assertStatus(404);
    }

    public function test_get_product_with_non_existent_id_after_auth(): void
    {
        $student = User::query()->where('user_id', 'student01')->first();
        $response = $this->actingAs($student)->getJson('/api/v1/product/99999');
        $response->assertStatus(404);
    }

    public function test_get_product_with_huge_id_after_auth(): void
    {
        $student = User::query()->where('user_id', 'student01')->first();
        $response = $this->actingAs($student)->getJson('/api/v1/product/999999999999');
        $response->assertStatus(404);
    }

    public function test_visitor_product_with_zero_id(): void
    {
        $response = $this->getJson('/api/v1/visitor/product/0');
        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_visitor_product_with_negative_id(): void
    {
        $response = $this->getJson('/api/v1/visitor/product/-1');
        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_visitor_product_with_non_existent_id(): void
    {
        $response = $this->getJson('/api/v1/visitor/product/99999');
        $response->assertOk();
        $response->assertJson([]);
    }

    public function test_increment_view_non_existent_product(): void
    {
        $response = $this->postJson('/api/v1/visitor/product/99999/view');
        $response->assertStatus(404);
    }

    public function test_increment_like_non_existent_product(): void
    {
        $response = $this->postJson('/api/v1/visitor/product/99999/like');
        $response->assertStatus(404);
    }

    public function test_increment_share_non_existent_product(): void
    {
        $response = $this->postJson('/api/v1/visitor/product/99999/share');
        $response->assertStatus(404);
    }

    public function test_search_sql_injection(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?q=' . urlencode("' OR '1'='1"));
        $response->assertOk();
    }

    public function test_search_xss_injection(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?q=' . urlencode('<script>alert("xss")</script>'));
        $response->assertStatus(422);
    }

    public function test_search_with_extremely_long_query(): void
    {
        $longQuery = str_repeat('a', 500);
        $response = $this->getJson('/api/v1/visitor/products/search?q=' . $longQuery);
        $response->assertStatus(422);
    }

    public function test_search_with_invalid_sort_by(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?sort_by=invalid_sort');
        $response->assertStatus(422);
    }

    public function test_search_with_negative_per_page(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?per_page=-1');
        $response->assertStatus(422);
    }

    public function test_search_with_huge_per_page(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?per_page=10000');
        $response->assertStatus(422);
    }

    public function test_get_visitor_products_with_invalid_major_id(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?major_id=abc');
        $response->assertStatus(422);
    }

    public function test_get_visitor_products_with_non_existent_major_id(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?major_id=99999');
        $response->assertOk();
    }

    public function test_get_visitor_products_with_invalid_sort(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?sort_by=invalid');
        $response->assertStatus(422);
    }

    public function test_delete_product_without_auth(): void
    {
        $product = Product::query()->first();
        $response = $this->deleteJson("/api/v1/student/product/{$product->product_id}");
        $response->assertStatus(401);
    }

    public function test_get_matching_ai_products_without_auth(): void
    {
        $response = $this->getJson('/api/v1/ai/compare/1');
        $response->assertStatus(401);
    }

    public function test_matching_ai_products_non_existent_id_returns_404(): void
    {
        $student = User::query()->where('user_id', 'student01')->first();
        $response = $this->actingAs($student)->getJson('/api/v1/ai/compare/99999');
        $response->assertStatus(404);
        $response->assertJsonPath('success', false);
        $response->assertJsonPath('message', 'Sản phẩm không tồn tại');
    }

    public function test_rate_limiting_on_login(): void
    {
        for ($i = 0; $i < 6; $i++) {
            $response = $this->postJson('/api/v1/login', [
                'username' => 'student01',
                'password' => 'wrongpass',
                'user_role' => 'student',
            ]);
        }

        $response->assertStatus(429);
    }
}
