<?php

namespace Tests\Feature;

use App\Models\Category;
use App\Models\Major;
use App\Models\Product;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Laravel\Sanctum\Sanctum;
use Tests\TestCase;

class TeacherReviewTest extends TestCase
{
    use RefreshDatabase;

    public function test_teacher_can_submit_review_for_product_in_their_major(): void
    {
        [$teacher, $product] = $this->createProductAndTeacher();
        Sanctum::actingAs($teacher);

        $response = $this->postJson("/api/v1/teacher/product/{$product->product_id}/reviews", [
            'comment' => 'Sản phẩm phù hợp với cách quản lý của trường.',
        ]);

        $response->assertCreated()
            ->assertJsonPath('result', true)
            ->assertJsonPath('data.teacher.fullname', $teacher->name)
            ->assertJsonPath('data.comment', 'Sản phẩm phù hợp với cách quản lý của trường.');

        $this->assertDatabaseHas('reviews', [
            'product_id' => $product->product_id,
            'teacher_id' => $teacher->user_id,
        ]);
    }

    public function test_teacher_cannot_review_product_from_another_major(): void
    {
        [$teacher, $product] = $this->createProductAndTeacher();
        $otherMajor = Major::create([
            'major_name' => 'Mạng máy tính',
            'major_code' => 'mmt',
        ]);
        $teacher->update(['major_id' => $otherMajor->major_id]);
        Sanctum::actingAs($teacher->fresh());

        $this->postJson("/api/v1/teacher/product/{$product->product_id}/reviews", [
            'comment' => 'Không được phép.',
        ])->assertForbidden();

        $this->assertDatabaseCount('reviews', 0);
    }

    private function createProductAndTeacher(): array
    {
        $major = Major::create([
            'major_name' => 'Công nghệ thông tin',
            'major_code' => 'cntt',
        ]);
        $category = Category::create(['category_name' => 'Website']);
        $teacher = $this->createUser('GV001', 'teacher@example.com', 'teacher', $major->major_id);
        $student = $this->createUser('SV001', 'student@example.com', 'student', $major->major_id);
        $product = Product::create([
            'title' => 'Sản phẩm thử nghiệm',
            'thumbnail' => 'https://example.com/image.jpg',
            'status' => 'pending',
            'user_id' => $student->user_id,
            'major_id' => $major->major_id,
            'cate_id' => $category->cate_id,
        ]);

        return [$teacher, $product];
    }

    private function createUser(string $id, string $email, string $role, int $majorId): User
    {
        return User::create([
            'user_id' => $id,
            'name' => $id,
            'email' => $email,
            'password' => Hash::make('password'),
            'role' => $role,
            'major_id' => $majorId,
        ]);
    }
}
