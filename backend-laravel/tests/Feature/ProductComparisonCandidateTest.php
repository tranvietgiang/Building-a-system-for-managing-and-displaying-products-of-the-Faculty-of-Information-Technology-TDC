<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Major;
use App\Models\Product;
use App\Models\ProductCNTT;
use App\Models\ProductGraphic;
use App\Models\ProductMMT;
use App\Models\User;
use App\Repositories\ProductRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Http;
use Laravel\Sanctum\Sanctum;
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

        $repository = app(ProductRepository::class);
        $matches = $repository->findMatchingAiProducts($first->product_id);
        $current = $repository->compareData($first->product_id);

        $this->assertCount(1, $matches);
        $this->assertSame($second->product_id, $matches[0]['product_id']);
        $this->assertSame('PHP', $current->programming_language);
        $this->assertSame('Laravel', $current->framework);
        $this->assertSame('MySQL', $current->database_used);
    }

    public function test_slightly_edited_network_product_is_considered_a_candidate(): void
    {
        $major = Major::create([
            'major_name' => 'Mạng máy tính và truyền thông dữ liệu',
            'major_code' => 'mmt',
        ]);
        $category = Category::create(['category_name' => 'Hệ thống mạng']);
        $user = User::create([
            'user_id' => 'SV002',
            'name' => 'Sinh viên MMT',
            'email' => 'network@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'major_id' => $major->major_id,
        ]);

        $first = Product::create([
            'title' => 'Hệ thống mạng doanh nghiệp bảo mật đa chi nhánh',
            'description' => 'Mô phỏng mạng doanh nghiệp có VLAN và tường lửa.',
            'thumbnail' => 'network-first.jpg',
            'user_id' => $user->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);
        $second = Product::create([
            'title' => 'Hệ thống mạng doanh nghiệp bảo mật cho nhiều chi nhánh',
            'description' => 'Mô phỏng mạng doanh nghiệp sử dụng VLAN và tường lửa.',
            'thumbnail' => 'network-second.jpg',
            'user_id' => $user->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);

        ProductMMT::create([
            'product_id' => $first->product_id,
            'simulation_tool' => 'Cisco Packet Tracer',
            'network_protocol' => 'Router, Switch Layer, Firewall, Access Point',
            'topology_type' => 'Mô hình hình sao kết hợp cây',
        ]);
        ProductMMT::create([
            'product_id' => $second->product_id,
            'simulation_tool' => 'Cisco Packet Tracer 8.2',
            'network_protocol' => 'Router, Switch Layer, Firewall, Access Point, VLAN',
            'topology_type' => 'Mô hình hình sao kết hợp cây có dự phòng',
        ]);

        $matches = app(ProductRepository::class)
            ->findMatchingAiProducts($first->product_id);

        $this->assertCount(1, $matches);
        $this->assertSame($second->product_id, $matches[0]['product_id']);
    }

    public function test_graphic_product_is_compared_even_when_its_detail_row_is_missing(): void
    {
        $major = Major::create([
            'major_name' => 'Thiết kế đồ họa',
            'major_code' => 'TKDH',
        ]);
        $category = Category::create(['category_name' => 'Thiết kế sáng tạo']);
        $user = User::create([
            'user_id' => 'SV003',
            'name' => 'Sinh viên Đồ họa',
            'email' => 'graphic@example.com',
            'password' => Hash::make('password'),
            'role' => 'student',
            'major_id' => $major->major_id,
        ]);

        $original = Product::create([
            'title' => 'Bộ nhận diện thương hiệu cà phê Mộc Nhiên',
            'description' => 'Thiết kế logo, bảng màu, bao bì, menu và các ấn phẩm truyền thông.',
            'thumbnail' => 'graphic-original.jpg',
            'user_id' => $user->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);
        ProductGraphic::create([
            'product_id' => $original->product_id,
            'design_type' => 'Bộ nhận diện thương hiệu',
            'tools_used' => 'Adobe Illustrator, Photoshop',
        ]);

        $editedCopy = Product::create([
            'title' => 'Bộ nhận diện thương hiệu cà phê Mộc Nhiên phiên bản mới',
            'description' => 'Thiết kế logo, bảng màu, bao bì và menu với một vài điều chỉnh nhỏ.',
            'thumbnail' => 'graphic-copy.jpg',
            'user_id' => $user->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);

        Http::fake([
            'api.openai.com/*' => Http::response([
                'choices' => [[
                    'message' => [
                        'content' => json_encode([
                            'similarity' => 94,
                            'level' => 'high',
                            'reason' => 'Hai sản phẩm có nội dung gần như trùng nhau.',
                        ]),
                    ],
                ]],
            ]),
        ]);
        Sanctum::actingAs($user);

        $response = $this->getJson('/api/v1/ai/compare/'.$editedCopy->product_id);

        $response->assertOk()
            ->assertJsonPath('success', true)
            ->assertJsonPath('status', true)
            ->assertJsonPath('summary.match_count', 1)
            ->assertJsonPath('matches.unapproved.0.product_id', $original->product_id);
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
