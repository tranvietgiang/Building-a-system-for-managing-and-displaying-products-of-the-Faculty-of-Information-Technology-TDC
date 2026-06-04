<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class ProductImageSeeder extends Seeder
{
    public function run(): void
    {
        DB::table('product_images')->truncate();

        $rows = [];
        $imageId = 1;

        /*
        |--------------------------------------------------------------------------
        | ẢNH GALLERY THEO ĐÚNG CHUYÊN NGÀNH
        | Mỗi sản phẩm lấy 4 ảnh. Nếu hết danh sách thì tự xoay vòng.
        |--------------------------------------------------------------------------
        */

        // Product ID 1 → 50: Trí tuệ nhân tạo
        $aiGalleryImages = [
            'https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386434/pexels-photo-8386434.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386441/pexels-photo-8386441.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386442/pexels-photo-8386442.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386443/pexels-photo-8386443.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386444/pexels-photo-8386444.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386445/pexels-photo-8386445.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386446/pexels-photo-8386446.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386447/pexels-photo-8386447.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/8386448/pexels-photo-8386448.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/6153354/pexels-photo-6153354.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/6153355/pexels-photo-6153355.jpeg?w=900&h=600&fit=crop&auto=compress',
        ];

        // Product ID 51 → 100: Công nghệ thông tin
        $cnttGalleryImages = [
            'https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/270404/pexels-photo-270404.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/546819/pexels-photo-546819.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/3861972/pexels-photo-3861972.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/3861958/pexels-photo-3861958.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/574071/pexels-photo-574071.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1181533/pexels-photo-1181533.jpeg?w=900&h=600&fit=crop&auto=compress',
        ];

        // Product ID 101 → 150: Mạng máy tính
        $mmtGalleryImages = [
            'https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/2588757/pexels-photo-2588757.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1148820/pexels-photo-1148820.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/2881232/pexels-photo-2881232.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/2881229/pexels-photo-2881229.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/2881233/pexels-photo-2881233.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/5380642/pexels-photo-5380642.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/5380649/pexels-photo-5380649.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg?w=900&h=600&fit=crop&auto=compress',
        ];

        // Product ID 151 → 200: Thiết kế đồ họa
        $graphicGalleryImages = [
            'https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1779487/pexels-photo-1779487.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/4348404/pexels-photo-4348404.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/326503/pexels-photo-326503.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/265087/pexels-photo-265087.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/4348401/pexels-photo-4348401.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/4348403/pexels-photo-4348403.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/4348405/pexels-photo-4348405.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/4348406/pexels-photo-4348406.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/6444/pencil-typography-black-design.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/1109541/pexels-photo-1109541.jpeg?w=900&h=600&fit=crop&auto=compress',
            'https://images.pexels.com/photos/3584994/pexels-photo-3584994.jpeg?w=900&h=600&fit=crop&auto=compress',
        ];

        $imageGroups = [
            ['from' => 1, 'to' => 50, 'images' => $aiGalleryImages],
            ['from' => 51, 'to' => 100, 'images' => $cnttGalleryImages],
            ['from' => 101, 'to' => 150, 'images' => $mmtGalleryImages],
            ['from' => 151, 'to' => 200, 'images' => $graphicGalleryImages],
        ];

        foreach ($imageGroups as $group) {
            for ($productId = $group['from']; $productId <= $group['to']; $productId++) {
                $offset = (($productId - $group['from']) * 4) % count($group['images']);

                for ($i = 0; $i < 4; $i++) {
                    $rows[] = [
                        'product_image_id' => $imageId++,
                        'product_id' => $productId,
                        'image_url' => $group['images'][($offset + $i) % count($group['images'])],
                        'created_at' => now(),
                        'updated_at' => now(),
                    ];
                }
            }
        }

        DB::table('product_images')->insert($rows);

        $this->command->info('✅ Đã insert ' . count($rows) . ' ảnh gallery đúng chuyên ngành cho 200 sản phẩm!');
    }
}
