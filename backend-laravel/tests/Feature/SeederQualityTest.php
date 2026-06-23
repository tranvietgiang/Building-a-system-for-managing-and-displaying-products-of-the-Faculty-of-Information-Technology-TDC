<?php

namespace Tests\Feature;

use Database\Seeders\DatabaseSeeder;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\DB;
use PHPUnit\Framework\Attributes\Group;
use Tests\TestCase;

#[Group('seeders')]
class SeederQualityTest extends TestCase
{
    use RefreshDatabase;

    public function test_catalog_has_twenty_products_per_major_and_five_gallery_images_each(): void
    {
        $this->seed(DatabaseSeeder::class);

        $counts = DB::table('products as p')
            ->join('majors as m', 'p.major_id', '=', 'm.major_id')
            ->selectRaw('m.major_code, COUNT(*) as total')
            ->groupBy('m.major_code')
            ->pluck('total', 'major_code')
            ->map(fn ($total) => (int) $total)
            ->all();
        ksort($counts);

        $this->assertSame([
            'AI' => 20,
            'CNTT' => 20,
            'MMT' => 20,
            'TKDH' => 20,
        ], $counts);

        $imageCounts = DB::table('products as p')
            ->leftJoin('product_images as image', 'p.product_id', '=', 'image.product_id')
            ->selectRaw('p.product_id, COUNT(image.product_image_id) as total')
            ->groupBy('p.product_id')
            ->pluck('total');

        $this->assertCount(80, $imageCounts);
        $this->assertTrue($imageCounts->every(fn ($total) => (int) $total === 5));
        $this->assertSame(0, DB::table('products')->whereRaw('LENGTH(description) < 100')->count());
        $this->assertSame(20, DB::table('product_ai')->count());
        $this->assertSame(20, DB::table('product_cntt')->count());
        $this->assertSame(20, DB::table('product_mmt')->count());
        $this->assertSame(20, DB::table('product_graphic')->count());
    }
}
