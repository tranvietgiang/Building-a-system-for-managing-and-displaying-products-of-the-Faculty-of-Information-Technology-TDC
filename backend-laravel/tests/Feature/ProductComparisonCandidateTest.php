<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Major;
use App\Models\Product;
use App\Models\ProductCNTT;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class ProductComparisonCandidateTest extends TestCase
{
    use RefreshDatabase;

    public function test_empty_fields_are_not_considered_matches(): void
    {
        [$first, $second] = $this->createDistinctProducts();

        $matches = app(ProductRepository::class)->findMatchingAiProducts($first->product_id);

        $this->assertSame([], $matches);
    }

    public function test_non_empty_matching_field_is_considered_a_match(): void
    {
        [$first, $second] = $this->createDistinctProducts();
        ProductCNTT::create([
            'product_id' => $first->product_id,
            'programming_language' => 'PHP',
            'framework' => 'Laravel',
            'database_used' => 'MySQL',
        ]);
        ProductCNTT::create([
            'product_id' => $second->product_id,
            'programming_language' => 'JavaScript',
            'framework' => 'Laravel',
            'database_used' => 'PostgreSQL',
        ]);

        $matches = app(ProductRepository::class)->findMatchingAiProducts($first->product_id);

        $this->assertCount(1, $matches);
        $this->assertSame($second->product_id, $matches[0]['product_id']);
    }

    private function createDistinctProducts(): array
    {
        $major = Major::create([
            'major_name' => 'Công nghệ thông tin',
            'major_code' => 'cntt',
        ]);
        $category = Category::create(['category_name' => 'Website']);
        $user = User::create([
            'user_id' => 'SV001',
            'name' => 'Sinh viên',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'major_id' => $major->major_id,
        ]);

        $first = Product::create([
            'title' => 'Quản lý thư viện',
            'description' => 'Ứng dụng quản lý sách',
            'thumbnail' => 'first.jpg',
            'user_id' => $user->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);
        $second = Product::create([
            'title' => 'Cửa hàng trực tuyến',
            'description' => 'Ứng dụng bán hàng',
            'thumbnail' => 'second.jpg',
            'user_id' => $user->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);

        return [$first, $second];
    }
}
