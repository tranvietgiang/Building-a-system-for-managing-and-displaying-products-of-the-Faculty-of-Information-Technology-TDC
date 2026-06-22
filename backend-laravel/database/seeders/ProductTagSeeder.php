<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductTagSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_tags')->delete();

        $products = DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->leftJoin('product_ai as ai', 'p.product_id', '=', 'ai.product_id')
            ->leftJoin('product_cntt as it', 'p.product_id', '=', 'it.product_id')
            ->leftJoin('product_mmt as nw', 'p.product_id', '=', 'nw.product_id')
            ->leftJoin('product_graphic as gr', 'p.product_id', '=', 'gr.product_id')
            ->select(
                'p.product_id', 'p.title', 'm.major_code',
                'ai.model_used', 'ai.framework as ai_framework', 'ai.language',
                'it.programming_language', 'it.framework as it_framework', 'it.database_used',
                'nw.simulation_tool', 'nw.network_protocol', 'nw.topology_type',
                'gr.design_type', 'gr.tools_used'
            )
            ->get();

        $rows = [];

        foreach ($products as $product) {
            $tags = match (strtoupper($product->major_code)) {
                'AI' => [$product->model_used, $product->ai_framework, $product->language, 'Machine Learning', $this->topicTag($product->title)],
                'CNTT' => [$product->programming_language, $product->it_framework, $product->database_used, 'Web Application', $this->topicTag($product->title)],
                'MMT' => [$product->simulation_tool, $product->topology_type, ...$this->splitValues($product->network_protocol)],
                'TKDH' => [$product->design_type, ...$this->splitValues($product->tools_used), 'Thiết kế đồ họa'],
                default => [],
            };

            foreach (array_slice(array_values(array_unique(array_filter($tags))), 0, 5) as $tag) {
                $rows[] = [
                    'product_id' => $product->product_id,
                    'tag_name' => mb_substr(trim($tag), 0, 50),
                    'created_at' => now(),
                    'updated_at' => now(),
                ];
            }
        }

        DB::table('product_tags')->insert($rows);
        $this->command->info('Đã tạo tag theo đúng thông tin từng sản phẩm.');
    }

    private function splitValues(?string $value): array
    {
        return array_values(array_filter(array_map('trim', preg_split('/[,;]/', (string) $value))));
    }

    private function topicTag(string $title): string
    {
        $title = mb_strtolower($title);

        return match (true) {
            str_contains($title, 'quản lý') => 'Quản lý',
            str_contains($title, 'nhận diện') => 'Nhận diện',
            str_contains($title, 'phát hiện') => 'Phát hiện',
            str_contains($title, 'dự báo'), str_contains($title, 'dự đoán') => 'Dự báo',
            str_contains($title, 'ứng dụng') => 'Mobile App',
            default => 'Ứng dụng thực tế',
        };
    }
}
