<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Major;
use App\Models\Product;
use App\Models\User;
use App\Services\ProductDuplicateService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Tests\TestCase;

class ProductDuplicateServiceTest extends TestCase
{
    use RefreshDatabase;

    public function test_exact_duplicate_is_blocked_before_ai_request(): void
    {
        Http::fake();
        [$major, $product] = $this->createExistingProduct();

        $duplicate = app(ProductDuplicateService::class)->check([
            'major_id' => $major->major_id,
            'title' => '  hệ thống quản lý sinh viên  ',
            'description' => 'Nội dung giống nhau',
        ]);

        $this->assertNotNull($duplicate);
        $this->assertSame($product->product_id, $duplicate['product_id']);
        $this->assertSame(100, $duplicate['similarity']);
        $this->assertSame('exact', $duplicate['method']);
        Http::assertNothingSent();
    }

    private function createExistingProduct(): array
    {
        $major = Major::create([
            'major_name' => 'Công nghệ thông tin',
            'major_code' => 'cntt',
        ]);
        $category = Category::create(['category_name' => 'Website']);
        $student = User::create([
            'user_id' => 'SV001',
            'name' => 'Sinh viên',
            'email' => 'student@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'major_id' => $major->major_id,
        ]);
        $product = Product::create([
            'title' => 'Hệ thống quản lý sinh viên',
            'description' => 'Nội dung giống nhau',
            'thumbnail' => 'image.jpg',
            'status' => 'pending',
            'user_id' => $student->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);

        return [$major, $product];
    }
}
