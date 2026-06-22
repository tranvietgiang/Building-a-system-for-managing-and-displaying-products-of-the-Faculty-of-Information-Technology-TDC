<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ReviewSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('reviews')->delete();

        $comments = [
            'Sản phẩm xác định rõ bài toán, dữ liệu trình bày đầy đủ và kết quả có khả năng ứng dụng thực tế.',
            'Nhóm xây dựng quy trình hợp lý, giao diện dễ theo dõi và phần kiểm thử được mô tả rõ ràng.',
            'Nội dung chuyên ngành tốt, minh chứng trực quan và hướng phát triển tiếp theo phù hợp.',
            'Sản phẩm hoàn thiện, thông tin nhất quán giữa phần mô tả, công nghệ và kết quả triển khai.',
            'Giải pháp có tính thực tiễn, bố cục trình bày sạch và nhóm giải thích được các lựa chọn kỹ thuật.',
        ];

        $products = DB::table('products')->orderBy('product_id')->get();
        $rows = [];

        foreach ($products as $index => $product) {
            $teacherId = DB::table('users')
                ->where('role', 'teacher')
                ->where('major_id', $product->major_id)
                ->value('user_id');

            if (! $teacherId) {
                continue;
            }

            $rows[] = [
                'product_id' => $product->product_id,
                'teacher_id' => $teacherId,
                'comment' => $comments[$index % count($comments)],
                'created_at' => now()->subDays(3),
                'updated_at' => now()->subDays(3),
            ];
        }

        DB::table('reviews')->insert($rows);
        $this->command->info('Đã tạo '.count($rows).' đánh giá từ giảng viên đúng chuyên ngành.');
    }
}
