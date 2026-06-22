<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductStatisticSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_statistics')->truncate();

        $rows = [];

        $products = DB::table('products')
            ->select('product_id')
            ->get();

        foreach ($products as $index => $p) {
            $rows[] = [
                'product_id' => $p->product_id,
                'views' => 120 + (($index * 47) % 880),
                'likes' => 12 + (($index * 7) % 95),
                'downloads' => 5 + (($index * 3) % 48),
                'shares' => 2 + (($index * 5) % 31),
                'created_at' => now(),
                'updated_at' => now(),
            ];
        }

        DB::table('product_statistics')->insert($rows);
    }
}
