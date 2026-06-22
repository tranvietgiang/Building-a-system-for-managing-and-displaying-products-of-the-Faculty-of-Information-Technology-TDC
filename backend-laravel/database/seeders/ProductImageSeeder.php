<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_images')->delete();

        $products = DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->select('p.product_id', 'm.major_code')
            ->orderBy('m.major_id')
            ->orderBy('p.product_id')
            ->get()
            ->groupBy(fn ($product) => strtoupper($product->major_code));

        $rows = [];

        foreach ($products as $majorCode => $majorProducts) {
            foreach ($majorProducts->values() as $index => $product) {
                $gallery = array_slice(ProductSeedImages::for($majorCode, $index), 1, 5);

                foreach ($gallery as $imageUrl) {
                    $rows[] = [
                        'product_id' => $product->product_id,
                        'image_url' => $imageUrl,
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        foreach (array_chunk($rows, 500) as $chunk) {
            DB::table('product_images')->insert($chunk);
        }

        $this->command->info('Đã tạo '.count($rows).' ảnh gallery (5 ảnh/sản phẩm).');
    }
}
