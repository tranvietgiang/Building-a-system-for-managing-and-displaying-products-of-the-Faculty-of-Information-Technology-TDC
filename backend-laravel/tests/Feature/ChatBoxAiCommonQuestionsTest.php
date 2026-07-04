<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ChatBoxAiCommonQuestionsTest extends TestCase
{
    use RefreshDatabase;

    private array $tokens = [];

    protected function setUp(): void
    {
        parent::setUp();

        DB::table('majors')->insert([
            ['major_id' => 1, 'major_name' => 'Artificial Intelligence', 'major_code' => 'AI', 'description' => 'Ngành trí tuệ nhân tạo và học máy.'],
            ['major_id' => 2, 'major_name' => 'Công nghệ thông tin', 'major_code' => 'CNTT', 'description' => 'Ngành phát triển phần mềm, website, hệ thống thông tin.'],
            ['major_id' => 3, 'major_name' => 'Mạng máy tính', 'major_code' => 'MMT', 'description' => 'Ngành quản trị mạng, an ninh mạng và hạ tầng hệ thống.'],
            ['major_id' => 4, 'major_name' => 'Thiết kế đồ họa', 'major_code' => 'TKDH', 'description' => 'Ngành thiết kế hình ảnh, thương hiệu và giao diện số.'],
        ]);

        $users = [
            // Students
            ['user_id' => '23211AI2',    'name' => 'Sinh vien AI 2',          'email' => '23211ai2@student.tdc.edu.vn',  'role' => 'student', 'major_id' => 1],
            ['user_id' => '23211CNTT1',  'name' => 'Sinh vien CNTT 1',        'email' => '23211cntt1@student.tdc.edu.vn','role' => 'student', 'major_id' => 2],
            ['user_id' => '23211MMT3',   'name' => 'Sinh vien MMT 3',         'email' => '23211mmt3@student.tdc.edu.vn', 'role' => 'student', 'major_id' => 3],
            ['user_id' => '23211TKDH4',  'name' => 'Sinh vien Thiet ke do hoa 4','email' => '23211tkdh4@student.tdc.edu.vn','role' => 'student', 'major_id' => 4],
            // Teachers
            ['user_id' => 'GVAI',        'name' => 'Giang vien AI',           'email' => 'gvai@tdc.edu.vn',              'role' => 'teacher', 'major_id' => 1],
            ['user_id' => 'GVCNTT',      'name' => 'Giang vien CNTT',         'email' => 'gvcntt@tdc.edu.vn',            'role' => 'teacher', 'major_id' => 2],
            ['user_id' => 'GVMMT',       'name' => 'Giang vien MMT',          'email' => 'gvmmt@tdc.edu.vn',             'role' => 'teacher', 'major_id' => 3],
            ['user_id' => 'GVTKDH',      'name' => 'Giang vien Thiet ke do hoa','email' => 'gvtkdh@tdc.edu.vn',          'role' => 'teacher', 'major_id' => 4],
            // Admin
            ['user_id' => 'admin',       'name' => 'Admin',                   'email' => 'admin@tdc.edu.vn',             'role' => 'admin',   'major_id' => null],
        ];

        foreach ($users as $data) {
            User::query()->create(array_merge($data, [
                'password' => bcrypt('12345678'),
            ]));
        }
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

    /* ══════════════════════════════════════════════════════════════
     *  1. VISITOR (no auth)
     * ══════════════════════════════════════════════════════════════ */

    public function test_visitor_asks_out_of_scope_questions(): void
    {
        $questions = [
            'Chào bạn',
            'Cảm ơn bạn nhiều',
            'Bạn có thể làm gì?',
            'Học ở đâu?',
            'Trường đại học nào?',
            'Bao nhiêu tuổi?',
            'Học phí bao nhiêu?',
            'Có chơi game không?',
        ];

        foreach ($questions as $question) {
            $response = $this->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
            $this->assertEmpty($response->json('products'));
        }
    }

    public function test_visitor_asks_about_majors(): void
    {
        $questions = [
            'Có những ngành nào?',
            'Cho xem sản phẩm AI',
            'Tìm đồ án công nghệ thông tin',
            'Sản phẩm mạng máy tính',
            'Đồ án thiết kế đồ họa',
        ];

        foreach ($questions as $question) {
            $response = $this->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_visitor_edge_cases(): void
    {
        // Empty message
        $response = $this->postJson('/api/v1/ai/send', ['message' => '']);
        $response->assertStatus(422);

        // Too short
        $response = $this->postJson('/api/v1/ai/send', ['message' => 'ab']);
        $response->assertStatus(422);

        // Very long
        $response = $this->postJson('/api/v1/ai/send', ['message' => str_repeat('a', 1001)]);
        $response->assertStatus(422);

        // Special characters
        $response = $this->postJson('/api/v1/ai/send', ['message' => '!@#$%^&*()']);
        $this->assertChatResponseStructure($response);
    }

    /* ══════════════════════════════════════════════════════════════
     *  2. STUDENT - AI (major_id = 1)
     * ══════════════════════════════════════════════════════════════ */

    public function test_student_ai_asks_common_questions(): void
    {
        $token = $this->loginAs('23211AI2', 'student');

        $questions = [
            'Cho em xem đồ án AI',
            'Có sản phẩm machine learning nào không?',
            'Tìm đồ án về học sâu',
            'Danh sách sản phẩm trí tuệ nhân tạo',
            'Thống kê đồ án trong ngành AI',
            'Top sản phẩm AI xem nhiều nhất',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_student_ai_cannot_access_other_majors(): void
    {
        $token = $this->loginAs('23211AI2', 'student');

        $crossMajorQuestions = [
            'Cho em xem đồ án CNTT'     => 'CNTT',
            'Sản phẩm mạng máy tính'    => 'MMT',
            'Tìm đồ án thiết kế đồ họa' => 'TKDH',
        ];

        foreach ($crossMajorQuestions as $question => $majorCode) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
            $reply = $response->json('reply');
            $this->assertStringContainsString('chỉ có thể xem', $reply);
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  3. STUDENT - CNTT (major_id = 2)
     * ══════════════════════════════════════════════════════════════ */

    public function test_student_cntt_asks_common_questions(): void
    {
        $token = $this->loginAs('23211CNTT1', 'student');

        $questions = [
            'Cho em xem đồ án CNTT',
            'Có sản phẩm web nào không?',
            'Tìm đồ án về laravel',
            'Danh sách sản phẩm công nghệ thông tin',
            'Thống kê đồ án CNTT',
            'Có ứng dụng mobile nào?',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_student_cntt_cannot_access_other_majors(): void
    {
        $token = $this->loginAs('23211CNTT1', 'student');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem sản phẩm AI',
        ]);

        $this->assertChatResponseStructure($response);
        $this->assertStringContainsString('chỉ có thể xem', $response->json('reply'));
    }

    /* ══════════════════════════════════════════════════════════════
     *  4. STUDENT - MMT (major_id = 3)
     * ══════════════════════════════════════════════════════════════ */

    public function test_student_mmt_asks_common_questions(): void
    {
        $token = $this->loginAs('23211MMT3', 'student');

        $questions = [
            'Cho xem đồ án mạng máy tính',
            'Sản phẩm bảo mật mạng',
            'Tìm đồ án về an ninh mạng',
            'Có đồ án MMT nào về cloud không',
            'Danh sách sản phẩm mạng',
            'Hệ thống phát hiện xâm nhập mạng',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_student_mmt_cannot_access_other_majors(): void
    {
        $token = $this->loginAs('23211MMT3', 'student');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem đồ án CNTT',
        ]);

        $this->assertChatResponseStructure($response);
        $this->assertStringContainsString('chỉ có thể xem', $response->json('reply'));
    }

    /* ══════════════════════════════════════════════════════════════
     *  5. STUDENT - TKDH (major_id = 4)
     * ══════════════════════════════════════════════════════════════ */

    public function test_student_tkdh_asks_common_questions(): void
    {
        $token = $this->loginAs('23211TKDH4', 'student');

        $questions = [
            'Cho xem đồ án thiết kế đồ họa',
            'Sản phẩm UI/UX',
            'Tìm poster đẹp',
            'Danh sách sản phẩm TKDH',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_student_tkdh_cannot_access_other_majors(): void
    {
        $token = $this->loginAs('23211TKDH4', 'student');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem đồ án MMT',
        ]);

        $this->assertChatResponseStructure($response);
        $this->assertStringContainsString('chỉ có thể xem', $response->json('reply'));
    }

    /* ══════════════════════════════════════════════════════════════
     *  5.5 STUDENT không có major_id
     * ══════════════════════════════════════════════════════════════ */

    public function test_student_without_major_gets_instructed(): void
    {
        $student = User::query()->create([
            'user_id' => 'student_no_major',
            'name' => 'Sinh vien khong co nganh',
            'email' => 'nonmajor@student.tdc.edu.vn',
            'password' => bcrypt('12345678'),
            'role' => 'student',
            'major_id' => null,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'username' => 'student_no_major',
            'password' => '12345678',
            'user_role' => 'student',
        ]);

        $token = $response->json('access_token');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho em xem đồ án',
        ]);

        $response->assertStatus(403);
        $reply = $response->json('reply');
        $this->assertStringContainsString('chưa được gán ngành học', $reply);
    }

    /* ══════════════════════════════════════════════════════════════
     *  6. TEACHER - AI (major_id = 1)
     * ══════════════════════════════════════════════════════════════ */

    public function test_teacher_ai_asks_common_questions(): void
    {
        $token = $this->loginAs('GVAI', 'teacher');

        $questions = [
            'Cho tôi xem đồ án AI',
            'Thống kê số lượng đồ án AI',
            'Review gần đây của lớp AI',
            'Sản phẩm AI nổi bật',
            'Tìm đồ án về học máy',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_teacher_ai_cannot_access_other_majors(): void
    {
        $token = $this->loginAs('GVAI', 'teacher');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem đồ án mạng máy tính',
        ]);

        $this->assertChatResponseStructure($response);
        $this->assertStringContainsString('chỉ có thể xem', $response->json('reply'));
    }

    /* ══════════════════════════════════════════════════════════════
     *  7. TEACHER - CNTT (major_id = 2)
     * ══════════════════════════════════════════════════════════════ */

    public function test_teacher_cntt_asks_common_questions(): void
    {
        $token = $this->loginAs('GVCNTT', 'teacher');

        $questions = [
            'Cho xem sản phẩm CNTT',
            'Bao nhiêu đồ án CNTT?',
            'Có sản phẩm laravel mới không?',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    public function test_teacher_cntt_cannot_access_other_majors(): void
    {
        $token = $this->loginAs('GVCNTT', 'teacher');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho xem đồ án AI',
        ]);

        $this->assertChatResponseStructure($response);
        $this->assertStringContainsString('chỉ có thể xem', $response->json('reply'));
    }

    /* ══════════════════════════════════════════════════════════════
     *  8. TEACHER - MMT (major_id = 3)
     * ══════════════════════════════════════════════════════════════ */

    public function test_teacher_mmt_asks_common_questions(): void
    {
        $token = $this->loginAs('GVMMT', 'teacher');

        $questions = [
            'Cho xem sản phẩm MMT',
            'Thống kê đồ án mạng máy tính',
            'Có đồ án về bảo mật không?',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  9. TEACHER - TKDH (major_id = 4)
     * ══════════════════════════════════════════════════════════════ */

    public function test_teacher_tkdh_asks_common_questions(): void
    {
        $token = $this->loginAs('GVTKDH', 'teacher');

        $questions = [
            'Cho xem sản phẩm TKDH',
            'Thiết kế đồ họa có bao nhiêu đồ án?',
            'Sản phẩm đồ họa nổi bật',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  10. ADMIN (no major restriction)
     * ══════════════════════════════════════════════════════════════ */

    public function test_admin_asks_system_wide_questions(): void
    {
        $token = $this->loginAs('admin', 'admin');

        $questions = [
            'Tổng quan hệ thống',
            'Bao nhiêu người dùng?',
            'Thống kê tất cả ngành',
            'Sản phẩm xem nhiều nhất',
            'Danh sách sản phẩm AI',
            'Cho xem sản phẩm CNTT',
            'Có đồ án MMT nào?',
            'Đồ án thiết kế đồ họa',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  11. TEACHER - feature-related questions
     * ══════════════════════════════════════════════════════════════ */

    public function test_teacher_asks_feature_questions(): void
    {
        $token = $this->loginAs('GVAI', 'teacher');

        $questions = [
            'Làm sao để duyệt đồ án?',
            'Cách kiểm tra trùng lặp',
            'Hướng dẫn đánh giá sản phẩm',
            'Bị lỗi upload ảnh',
            'So sánh đồ án trùng',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  12. STUDENT - feature-related questions
     * ══════════════════════════════════════════════════════════════ */

    public function test_student_asks_feature_questions(): void
    {
        $token = $this->loginAs('23211AI2', 'student');

        $questions = [
            'Làm sao để nộp đồ án?',
            'Cách upload sản phẩm',
            'Bị lỗi upload',
        ];

        foreach ($questions as $question) {
            $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
                'message' => $question,
            ]);

            $this->assertChatResponseStructure($response);
        }
    }

    /* ══════════════════════════════════════════════════════════════
     *  13. Teacher without major_id
     * ══════════════════════════════════════════════════════════════ */

    public function test_teacher_without_major_gets_instructed(): void
    {
        User::query()->create([
            'user_id' => 'teacher_no_major',
            'name' => 'GV khong co nganh',
            'email' => 'nonmajor@teacher.tdc.edu.vn',
            'password' => bcrypt('12345678'),
            'role' => 'teacher',
            'major_id' => null,
        ]);

        $response = $this->postJson('/api/v1/login', [
            'username' => 'teacher_no_major',
            'password' => '12345678',
            'user_role' => 'lecturer',
        ]);

        $token = $response->json('access_token');

        $response = $this->withToken($token)->postJson('/api/v1/ai/send', [
            'message' => 'Cho tôi xem đồ án',
        ]);

        $response->assertStatus(403);
        $reply = $response->json('reply');
        $this->assertStringContainsString('chưa được gán ngành học', $reply);
    }
}
