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

        foreach ($products as $p) {

            $rows[] = [
                'product_id'   => $p->product_id,
                'views'        => 0,
                'likes'        => 0,
                'downloads'    => 0,
                'shares'       => 0,
                'created_at'   => now(),
                'updated_at'   => now(),
            ];
        }

        DB::table('product_statistics')->insert($rows);
    }
}
