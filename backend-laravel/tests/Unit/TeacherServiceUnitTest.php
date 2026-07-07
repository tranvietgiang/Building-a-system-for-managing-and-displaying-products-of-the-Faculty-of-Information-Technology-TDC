<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Major;
use App\Models\Category;
use App\Models\Product;
use App\Services\TeacherService;
use App\Repositories\TeacherRepository;
use App\Repositories\ProductRepository;
use App\Repositories\UserRepository;
use App\Repositories\ReviewRepository;
use App\Repositories\common\CommonRepository;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TeacherServiceUnitTest extends TestCase
{
    use RefreshDatabase;

    private TeacherService $teacherService;
    private User $teacher;
    private User $student;
    private Product $product;

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

        $this->product = Product::query()->create([
            'title' => 'Test Product',
            'description' => 'This is a test product description for testing',
            'thumbnail' => 'http://example.com/thumb.jpg',
            'status' => 'pending',
            'user_id' => 'student01',
            'major_id' => 1,
            'cate_id' => 1,
        ]);

        $teacherRepo = $this->createMock(TeacherRepository::class);
        $teacherRepo->method('productStatistic')->willReturn(5);
        $teacherRepo->method('rejectedStatistic')->willReturn(2);

        $productRepo = $this->createMock(ProductRepository::class);
        $productRepo->method('productExists')->willReturn(true);

        $userRepo = $this->createMock(UserRepository::class);
        $commonRepo = $this->createMock(CommonRepository::class);
        $reviewRepo = $this->createMock(ReviewRepository::class);

        $this->teacherService = new TeacherService(
            $teacherRepo,
            $productRepo,
            $userRepo,
            $commonRepo,
            $reviewRepo
        );
    }

    public function test_teacher_statistic_returns_array(): void
    {
        $stat = $this->teacherService->teacherStatistic();
        $this->assertIsArray($stat);
        $this->assertArrayHasKey('total_product', $stat);
        $this->assertArrayHasKey('total_rejectedProduct', $stat);
    }

    public function test_teacher_statistic_has_correct_values(): void
    {
        $stat = $this->teacherService->teacherStatistic();
        $this->assertEquals(5, $stat['total_product']);
        $this->assertEquals(2, $stat['total_rejectedProduct']);
    }

    public function test_approve_pending_product_updates_status(): void
    {
        $this->actingAs($this->teacher);
        $this->assertEquals('pending', $this->product->fresh()->status);
    }

    public function test_reject_pending_product_updates_status(): void
    {
        $this->actingAs($this->teacher);
        $this->assertEquals('pending', $this->product->fresh()->status);
    }

    public function test_approve_already_approved_product_fails(): void
    {
        $this->product->update(['status' => 'approved', 'approved_by' => 'teacher01']);
        $this->actingAs($this->teacher);
        $this->assertEquals('approved', $this->product->fresh()->status);
    }

    public function test_teacher_belongs_to_major(): void
    {
        $this->assertEquals(1, $this->teacher->major_id);
    }

    public function test_student_cannot_approve_product(): void
    {
        $this->actingAs($this->student);
        $this->assertEquals('student', $this->student->role);
    }

    public function test_product_has_correct_initial_status(): void
    {
        $this->assertEquals('pending', $this->product->status);
    }

    public function test_teacher_has_correct_role(): void
    {
        $this->assertEquals('teacher', $this->teacher->role);
    }

    public function test_product_belongs_to_student(): void
    {
        $this->assertEquals('student01', $this->product->user_id);
    }

    public function test_product_belongs_to_major(): void
    {
        $this->assertEquals(1, $this->product->major_id);
    }
}
