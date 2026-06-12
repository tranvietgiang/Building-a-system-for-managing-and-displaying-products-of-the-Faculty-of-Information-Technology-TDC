<?php

namespace Database\Seeders;

use App\Models\Category;
use App\Models\Major;
use Illuminate\Database\Seeder;
use App\Models\Product;
use App\Models\ProductAi;
use App\Models\ProductCNTT;
use App\Models\ProductMMT;
use App\Models\ProductGraphic;
use App\Models\User;

class ProductSeeder extends Seeder
{
    private const PRODUCT_FREE_TEST_USERS = [
        '00000TT2984',
        '11111TT2984',
        '22222TT2984',
        '33333TT2984',
    ];

    public function run(): void
    {
        $this->seedAI();
        $this->seedCNTT();
        $this->seedMMT();
        $this->seedGraphic();
    }

    /*
    |--------------------------------------------------------------------------
    | DANH SÁCH 50 ẢNH DEFAULT CHO TỪNG NGÀNH (TỔNG 200 ẢNH)
    |--------------------------------------------------------------------------
    */

    private function getAIThumbnails()
    {
        return [
            'https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386434/pexels-photo-8386434.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386441/pexels-photo-8386441.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386442/pexels-photo-8386442.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386443/pexels-photo-8386443.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386444/pexels-photo-8386444.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386445/pexels-photo-8386445.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386446/pexels-photo-8386446.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386447/pexels-photo-8386447.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386448/pexels-photo-8386448.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386449/pexels-photo-8386449.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386450/pexels-photo-8386450.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386451/pexels-photo-8386451.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386452/pexels-photo-8386452.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386453/pexels-photo-8386453.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386454/pexels-photo-8386454.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386455/pexels-photo-8386455.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386456/pexels-photo-8386456.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386457/pexels-photo-8386457.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386458/pexels-photo-8386458.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386459/pexels-photo-8386459.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386460/pexels-photo-8386460.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386461/pexels-photo-8386461.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386462/pexels-photo-8386462.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153354/pexels-photo-6153354.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153355/pexels-photo-6153355.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153356/pexels-photo-6153356.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153357/pexels-photo-6153357.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153358/pexels-photo-6153358.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153359/pexels-photo-6153359.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153360/pexels-photo-6153360.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153361/pexels-photo-6153361.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153362/pexels-photo-6153362.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153363/pexels-photo-6153363.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153364/pexels-photo-6153364.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153365/pexels-photo-6153365.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153366/pexels-photo-6153366.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153367/pexels-photo-6153367.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153368/pexels-photo-6153368.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153369/pexels-photo-6153369.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153370/pexels-photo-6153370.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153371/pexels-photo-6153371.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153372/pexels-photo-6153372.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153373/pexels-photo-6153373.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153374/pexels-photo-6153374.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153375/pexels-photo-6153375.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153376/pexels-photo-6153376.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153377/pexels-photo-6153377.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/6153378/pexels-photo-6153378.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg?w=800&h=450&fit=crop',
        ];
    }

    private function getCNTTThumbnails()
    {
        return [
            'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181468/pexels-photo-1181468.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181533/pexels-photo-1181533.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181243/pexels-photo-1181243.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181269/pexels-photo-1181269.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181248/pexels-photo-1181248.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181534/pexels-photo-1181534.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181265/pexels-photo-1181265.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181672/pexels-photo-1181672.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181242/pexels-photo-1181242.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181532/pexels-photo-1181532.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181466/pexels-photo-1181466.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181247/pexels-photo-1181247.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181673/pexels-photo-1181673.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181264/pexels-photo-1181264.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181674/pexels-photo-1181674.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181249/pexels-photo-1181249.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181469/pexels-photo-1181469.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181531/pexels-photo-1181531.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181245/pexels-photo-1181245.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181266/pexels-photo-1181266.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181676/pexels-photo-1181676.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181246/pexels-photo-1181246.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181535/pexels-photo-1181535.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181267/pexels-photo-1181267.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181677/pexels-photo-1181677.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181250/pexels-photo-1181250.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181470/pexels-photo-1181470.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181536/pexels-photo-1181536.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181268/pexels-photo-1181268.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181678/pexels-photo-1181678.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181251/pexels-photo-1181251.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181471/pexels-photo-1181471.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181537/pexels-photo-1181537.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181260/pexels-photo-1181260.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181679/pexels-photo-1181679.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181252/pexels-photo-1181252.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181472/pexels-photo-1181472.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181538/pexels-photo-1181538.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181261/pexels-photo-1181261.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181670/pexels-photo-1181670.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181253/pexels-photo-1181253.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181473/pexels-photo-1181473.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181539/pexels-photo-1181539.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181262/pexels-photo-1181262.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/1181240/pexels-photo-1181240.jpeg?w=800&h=450&fit=crop',
        ];
    }

    private function getMMTThumbnails()
    {
        return [
            'https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588757/pexels-photo-2588757.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588753/pexels-photo-2588753.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588758/pexels-photo-2588758.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588759/pexels-photo-2588759.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588760/pexels-photo-2588760.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588761/pexels-photo-2588761.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588762/pexels-photo-2588762.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588763/pexels-photo-2588763.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588764/pexels-photo-2588764.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588765/pexels-photo-2588765.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588766/pexels-photo-2588766.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588767/pexels-photo-2588767.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588768/pexels-photo-2588768.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588769/pexels-photo-2588769.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588770/pexels-photo-2588770.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588771/pexels-photo-2588771.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588772/pexels-photo-2588772.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588773/pexels-photo-2588773.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588774/pexels-photo-2588774.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588775/pexels-photo-2588775.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588776/pexels-photo-2588776.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588777/pexels-photo-2588777.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588778/pexels-photo-2588778.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588779/pexels-photo-2588779.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588780/pexels-photo-2588780.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588781/pexels-photo-2588781.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588782/pexels-photo-2588782.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588783/pexels-photo-2588783.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588784/pexels-photo-2588784.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588785/pexels-photo-2588785.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588786/pexels-photo-2588786.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588787/pexels-photo-2588787.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588788/pexels-photo-2588788.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588789/pexels-photo-2588789.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588790/pexels-photo-2588790.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588791/pexels-photo-2588791.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588792/pexels-photo-2588792.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588793/pexels-photo-2588793.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588794/pexels-photo-2588794.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588795/pexels-photo-2588795.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588796/pexels-photo-2588796.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588797/pexels-photo-2588797.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588798/pexels-photo-2588798.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588799/pexels-photo-2588799.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588757/pexels-photo-2588757.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/2588753/pexels-photo-2588753.jpeg?w=800&h=450&fit=crop',
        ];
    }

    private function getGraphicThumbnails()
    {
        return [
            'https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265697/pexels-photo-265697.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265705/pexels-photo-265705.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265702/pexels-photo-265702.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265703/pexels-photo-265703.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265704/pexels-photo-265704.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265706/pexels-photo-265706.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265707/pexels-photo-265707.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265708/pexels-photo-265708.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265709/pexels-photo-265709.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265710/pexels-photo-265710.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265711/pexels-photo-265711.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265712/pexels-photo-265712.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265713/pexels-photo-265713.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265714/pexels-photo-265714.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265715/pexels-photo-265715.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265716/pexels-photo-265716.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265717/pexels-photo-265717.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265718/pexels-photo-265718.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265719/pexels-photo-265719.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265720/pexels-photo-265720.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265721/pexels-photo-265721.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265722/pexels-photo-265722.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265723/pexels-photo-265723.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265724/pexels-photo-265724.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265725/pexels-photo-265725.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265726/pexels-photo-265726.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265727/pexels-photo-265727.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265728/pexels-photo-265728.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265729/pexels-photo-265729.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265730/pexels-photo-265730.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265731/pexels-photo-265731.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265732/pexels-photo-265732.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265733/pexels-photo-265733.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265734/pexels-photo-265734.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265735/pexels-photo-265735.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265736/pexels-photo-265736.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265737/pexels-photo-265737.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265738/pexels-photo-265738.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265739/pexels-photo-265739.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265740/pexels-photo-265740.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/257904/pexels-photo-257904.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265697/pexels-photo-265697.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265705/pexels-photo-265705.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265702/pexels-photo-265702.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265703/pexels-photo-265703.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265704/pexels-photo-265704.jpeg?w=800&h=450&fit=crop',
            'https://images.pexels.com/photos/265706/pexels-photo-265706.jpeg?w=800&h=450&fit=crop',
        ];
    }

    /*
    |--------------------------------------------------------------------------
    | AI - 50 sản phẩm
    |--------------------------------------------------------------------------
    */

    private function seedAI()
    {
        // 50 tên sản phẩm AI
        $titles = [
            'Hệ thống nhận diện khuôn mặt sinh viên bằng CNN',
            'Nhận diện biển số xe thông minh',
            'Chatbot tư vấn tuyển sinh',
            'Dự đoán giá nhà bằng Machine Learning',
            'Phân loại rác thải thông minh',
            'Nhận diện cảm xúc khuôn mặt',
            'Dịch máy Anh - Việt',
            'Nhận dạng chữ viết tay',
            'Hệ thống gợi ý khóa học',
            'Phát hiện khẩu trang nơi công cộng',
            'Phân tích cảm xúc khách hàng',
            'Dự báo doanh số bán hàng',
            'Phát hiện gian lận thẻ tín dụng',
            'Tối ưu hóa chuỗi cung ứng',
            'Hệ thống phát hiện spam',
            'Phân nhóm khách hàng',
            'Hệ thống gợi ý sản phẩm',
            'Nhận diện giống chó qua ảnh',
            'Phát hiện bệnh qua ảnh X-quang',
            'Hệ thống đánh giá rủi ro',
            'Phát hiện Deepfake video',
            'Tự động sinh code từ mô tả',
            'Nhận diện cử chỉ tay',
            'Hệ thống phát hiện xâm nhập',
            'Dự đoán nhu cầu năng lượng',
            'Phân loại văn bản tự động',
            'Hệ thống trả lời câu hỏi',
            'Nhận diện hành vi bất thường',
            'Tối ưu hóa lịch trình bằng AI',
            'Phát hiện đạo văn',
            'Hệ thống tóm tắt văn bản',
            'Nhận diện ký tự quang học',
            'Dự đoán thời tiết bằng ML',
            'Phân tích dữ liệu y tế',
            'Hệ thống tư vấn sức khỏe',
            'Nhận diện phương tiện giao thông',
            'Tự động phân loại email',
            'Hệ thống phát hiện bất thường',
            'Tối ưu hóa logistics',
            'Nhận diện hoa văn vải',
            'Phân tích giọng nói y tế',
            'Hệ thống xe tự lái cơ bản',
            'Nhận diện ngôn ngữ ký hiệu',
            'Tạo nhạc bằng AI',
            'Phát hiện stress qua giọng nói',
            'Hệ thống nhận diện giọng nói',
            'Phân tích hành vi người dùng',
            'Dự đoán khả năng trả nợ',
            'Hệ thống chatbot bán hàng',
            'Nhận diện sản phẩm bằng AI',
        ];

        $models = ['CNN', 'RNN', 'LSTM', 'YOLOv8', 'ResNet50', 'BERT', 'GPT', 'Transformer', 'GAN', 'ViT'];
        $frameworks = ['TensorFlow', 'PyTorch', 'Scikit-learn', 'Keras', 'OpenAI', 'Hugging Face'];
        $languages = ['Python', 'R', 'Java', 'C++'];
        $datasets = ['COCO', 'ImageNet', 'MNIST', 'CIFAR-10', 'SQuAD', 'WikiText'];

        $thumbnails = $this->getAIThumbnails();
        $students = User::where('role', 'student')->pluck('user_id')->toArray();
        $teachers = User::where('role', 'teacher')->pluck('user_id')->toArray();

        if (empty($students)) {
            $students = [User::first()->user_id];
        }
        if (empty($teachers)) {
            $teachers = [User::first()->user_id];
        }

        for ($i = 0; $i < 50; $i++) {
            $product = $this->createProduct(
                $titles[$i] . ' v' . ($i + 1),
                'Ứng dụng AI giúp tự động hóa quy trình, tiết kiệm thời gian và chi phí.',
                1, // major_id AI = 1
                $thumbnails[$i]
            );

            ProductAi::create([
                'product_id' => $product->product_id,
                'model_used' => $models[array_rand($models)],
                'framework' => $frameworks[array_rand($frameworks)],
                'language' => $languages[array_rand($languages)],
                'dataset_used' => $datasets[array_rand($datasets)],
                'accuracy_score' => rand(85, 99) . '.' . rand(0, 9),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | CNTT - 50 sản phẩm
    |--------------------------------------------------------------------------
    */

    private function seedCNTT()
    {
        // 50 tên sản phẩm CNTT
        $titles = [
            'Hệ thống quản lý sinh viên trực tuyến',
            'Website thương mại điện tử',
            'Ứng dụng đặt đồ ăn online',
            'Hệ thống quản lý thư viện',
            'Website tin tức tổng hợp',
            'Hệ thống đặt vé xem phim',
            'Platform học trực tuyến LMS',
            'Ứng dụng quản lý công việc',
            'Hệ thống chấm công tự động',
            'Website giới thiệu sản phẩm',
            'Hệ thống quản lý bán hàng',
            'Ứng dụng chat realtime',
            'Hệ thống đặt lịch khám bệnh',
            'Website tuyển dụng việc làm',
            'Quản lý kho hàng thông minh',
            'Hệ thống thanh toán trực tuyến',
            'Website đánh giá sản phẩm',
            'Ứng dụng ghi chú cá nhân',
            'Hệ thống quản lý sự kiện',
            'Website rao vặt miễn phí',
            'Hệ thống quản lý tài sản',
            'Ứng dụng theo dõi sức khỏe',
            'Hệ thống quản lý đề tài NCKH',
            'Website hỏi đáp Q&A',
            'Hệ thống quản lý dự án',
            'Ứng dụng học ngoại ngữ',
            'Hệ thống quản lý nhân sự',
            'Website review phim ảnh',
            'Hệ thống đặt phòng khách sạn',
            'Ứng dụng quản lý chi tiêu',
            'Hệ thống quản lý học phí',
            'Website giới thiệu ẩm thực',
            'Hệ thống đăng ký môn học',
            'Ứng dụng tra cứu điểm thi',
            'Hệ thống quản lý xe buýt',
            'Website bất động sản',
            'Hệ thống quản lý thực tập',
            'Ứng dụng nhắc nhở thông minh',
            'Hệ thống quản lý câu lạc bộ',
            'Website du lịch trực tuyến',
            'Hệ thống quản lý bãi đỗ xe',
            'Ứng dụng đọc truyện online',
            'Hệ thống quản lý cửa hàng',
            'Website thể thao trực tuyến',
            'Hệ thống quản lý thẻ thư viện',
            'Ứng dụng theo dõi thói quen',
            'Hệ thống quản lý thi đua',
            'Website âm nhạc online',
            'Hệ thống quản lý vận tải',
            'Ứng dụng học code tương tác',
        ];

        $languages = ['PHP', 'JavaScript', 'Java', 'C#', 'Python', 'Go', 'Ruby', 'Kotlin', 'TypeScript', 'Swift'];
        $frameworks = ['Laravel', 'React', 'VueJS', 'Angular', 'Spring Boot', '.NET', 'Django', 'Flask', 'Express', 'NextJS'];
        $databases = ['MySQL', 'PostgreSQL', 'MongoDB', 'SQL Server', 'Oracle', 'Firebase', 'Redis', 'MariaDB'];

        $thumbnails = $this->getCNTTThumbnails();
        $students = User::where('role', 'student')->pluck('user_id')->toArray();
        $teachers = User::where('role', 'teacher')->pluck('user_id')->toArray();

        if (empty($students)) {
            $students = [User::first()->user_id];
        }
        if (empty($teachers)) {
            $teachers = [User::first()->user_id];
        }

        for ($i = 0; $i < 50; $i++) {
            $product = $this->createProduct(
                $titles[$i] . ' v' . ($i + 1),
                'Đề tài thuộc ngành Công nghệ thông tin.',
                2, // major_id CNTT = 2
                $thumbnails[$i]
            );

            ProductCNTT::create([
                'product_id' => $product->product_id,
                'programming_language' => $languages[array_rand($languages)],
                'framework' => $frameworks[array_rand($frameworks)],
                'database_used' => $databases[array_rand($databases)],
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | MMT - 50 sản phẩm
    |--------------------------------------------------------------------------
    */

    private function seedMMT()
    {
        // 50 tên sản phẩm Mạng máy tính
        $titles = [
            'Thiết kế mạng LAN cho doanh nghiệp',
            'Hệ thống VLAN cho trường học',
            'Mô phỏng định tuyến OSPF',
            'Triển khai Firewall cho công ty',
            'Hệ thống giám sát mạng Zabbix',
            'Mạng VPN an toàn cho văn phòng',
            'Thiết kế mạng cho bệnh viện',
            'Hệ thống cân bằng tải',
            'Mô phỏng tấn công DDoS',
            'Triển khai mạng không dây',
            'Hệ thống phân giải tên miền DNS',
            'Mạng SDN cơ bản',
            'Bảo mật mạng nội bộ',
            'Hệ thống phát hiện xâm nhập IDS',
            'Mô hình mạng Data Center',
            'Triển khai dịch vụ DHCP',
            'Hệ thống VoIP cho doanh nghiệp',
            'Mạng kết nối đa chi nhánh',
            'Hệ thống giám sát băng thông',
            'Mô phỏng giao thức BGP',
            'Triển khai mạng Storage',
            'Hệ thống quản lý thiết bị mạng',
            'Mạng thông minh cho tòa nhà',
            'Triển khai dịch vụ Web Server',
            'Hệ thống backup dữ liệu qua mạng',
            'Mạng ảo hóa với VMware',
            'Triển khai mạng cho khách sạn',
            'Hệ thống log tập trung',
            'Mô phỏng giao thức STP',
            'Triển khai mạng cho trường học',
            'Hệ thống truyền thông nội bộ',
            'Mạng di động 5G cơ bản',
            'Triển khai dịch vụ Mail Server',
            'Hệ thống quản lý cấu hình mạng',
            'Mạng IoT cho nhà thông minh',
            'Triển khai VPN site-to-site',
            'Hệ thống phân tích lưu lượng',
            'Mạng chịu lỗi cao',
            'Triển khai dịch vụ Proxy',
            'Hệ thống quản lý IP',
            'Mạng cho sân bay quy mô nhỏ',
            'Triển khai hệ thống RADIUS',
            'Hệ thống giám sát ứng dụng',
            'Mạng quang học cơ bản',
            'Triển khai dịch vụ FTP Server',
            'Hệ thống quản lý băng tần',
            'Mạng cho trung tâm dữ liệu',
            'Triển khai hệ thống Syslog',
            'Hệ thống cảnh báo trực quan',
            'Mạng hybrid cloud cơ bản',
        ];

        $tools = ['Cisco Packet Tracer', 'GNS3', 'EVE-NG', 'VMware', 'VirtualBox', 'Wireshark', 'Nmap'];
        $protocols = ['VLAN', 'OSPF', 'RIP', 'EIGRP', 'BGP', 'DHCP', 'DNS', 'HTTP/HTTPS', 'SSH', 'FTP', 'SMTP'];
        $topologies = ['Star', 'Mesh', 'Tree', 'Hybrid', 'Bus', 'Ring'];

        $thumbnails = $this->getMMTThumbnails();
        $students = User::where('role', 'student')->pluck('user_id')->toArray();
        $teachers = User::where('role', 'teacher')->pluck('user_id')->toArray();

        if (empty($students)) {
            $students = [User::first()->user_id];
        }
        if (empty($teachers)) {
            $teachers = [User::first()->user_id];
        }

        for ($i = 0; $i < 50; $i++) {
            $product = $this->createProduct(
                $titles[$i] . ' v' . ($i + 1),
                'Đề tài thuộc ngành Mạng máy tính.',
                3, // major_id MMT = 3
                $thumbnails[$i]
            );

            ProductMMT::create([
                'product_id' => $product->product_id,
                'network_protocol' => $protocols[array_rand($protocols)],
                'topology_type' => $topologies[array_rand($topologies)],
                'simulation_tool' => $tools[array_rand($tools)],
                'config_file' => 'configs/config_' . ($i + 1) . '.txt',
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Graphic - 50 sản phẩm
    |--------------------------------------------------------------------------
    */

    private function seedGraphic()
    {
        // 50 tên sản phẩm Đồ họa
        $titles = [
            'Thiết kế logo thương hiệu cà phê',
            'Poster tuyển sinh đại học',
            'Bộ nhận diện thương hiệu mới',
            'UI/UX cho ứng dụng học tập',
            'Banner quảng cáo mùa hè',
            'Bao bì sản phẩm nước giải khát',
            'Brochure du lịch Đà Nẵng',
            'Flyer khuyến mãi đặc biệt',
            'Magazine ẩm thực đường phố',
            'Infographic về biến đổi khí hậu',
            'Thiết kế web bán hàng',
            'Poster phim điện ảnh',
            'Bộ nhận diện thương hiệu spa',
            'UI cho app đặt đồ ăn',
            'Banner Black Friday siêu sale',
            'Bao bì mỹ phẩm cao cấp',
            'Brochure giới thiệu công ty',
            'Flyer sự kiện âm nhạc',
            'Magazine thời trang xuân hè',
            'Infographic về an toàn giao thông',
            'Thiết kế logo startup công nghệ',
            'Poster bảo vệ môi trường',
            'Bộ nhận diện quán cà phê',
            'UI cho website du lịch',
            'Banner Tết Nguyên đán',
            'Bao bì thực phẩm organic',
            'Brochure bệnh viện quốc tế',
            'Flyer khai trương cửa hàng',
            'Magazine kiến trúc hiện đại',
            'Infographic về sức khỏe',
            'Thiết kế logo thể thao',
            'Poster cuộc thi sáng tạo',
            'Bộ nhận diện thương hiệu thời trang',
            'UI cho app tài chính',
            'Banner khuyến mãi mua sắm',
            'Bao bì sản phẩm công nghệ',
            'Brochure trung tâm đào tạo',
            'Flyer tuyển dụng nhân sự',
            'Magazine nghệ thuật số',
            'Infographic về giáo dục',
            'Thiết kế logo tổ chức thiện nguyện',
            'Poster sự kiện thể thao',
            'Bộ nhận diện thương hiệu trà sữa',
            'UI cho app tập gym',
            'Banner giáng sinh an lành',
            'Bao bì quà tặng cao cấp',
            'Thiết kế menu nhà hàng',
            'Poster khai trương',
            'Bộ nhận diện thương hiệu salon tóc',
            'UI cho app đặt xe',
            'Banner lễ hội mùa xuân',
            'Bao bì sản phẩm handmade',
        ];

        $designTypes = ['Logo', 'Poster', 'Banner', 'UI/UX', 'Branding', 'Packaging', 'Brochure', 'Flyer', 'Magazine', 'Infographic'];
        $tools = ['Adobe Illustrator', 'Adobe Photoshop', 'Figma', 'CorelDRAW', 'Adobe InDesign', 'Adobe XD', 'Sketch', 'Blender', 'Cinema 4D'];

        $thumbnails = $this->getGraphicThumbnails();
        $students = User::where('role', 'student')->pluck('user_id')->toArray();
        $teachers = User::where('role', 'teacher')->pluck('user_id')->toArray();

        if (empty($students)) {
            $students = [User::first()->user_id];
        }
        if (empty($teachers)) {
            $teachers = [User::first()->user_id];
        }

        for ($i = 0; $i < 50; $i++) {
            $product = $this->createProduct(
                $titles[$i] . ' v' . ($i + 1),
                'Đề tài thuộc ngành Thiết kế đồ họa.',
                4, // major_id Graphic = 4
                $thumbnails[$i]
            );

            ProductGraphic::create([
                'product_id' => $product->product_id,
                'design_type' => $designTypes[array_rand($designTypes)],
                'tools_used' => $tools[array_rand($tools)],
                'drive_link' => 'https://drive.google.com/drive/folders/tdc/graphic-' . ($i + 1),
                'behance_link' => 'https://www.behance.net/tdc/graphic-' . ($i + 1),
            ]);
        }
    }

    /*
    |--------------------------------------------------------------------------
    | Product chung
    |--------------------------------------------------------------------------
    */
    private function getRandomStatus(): string
    {
        $statuses = ['pending', 'approved', 'rejected'];

        return $statuses[array_rand($statuses)];
    }

    private function createProduct($title, $description, $majorId, $thumbnail)
    {
        $student = User::where('role', 'student')
            ->where('major_id', $majorId)
            ->whereNotIn('user_id', self::PRODUCT_FREE_TEST_USERS)
            ->inRandomOrder()
            ->first();

        $teacher = User::where('role', 'teacher')
            ->inRandomOrder()
            ->first();

        if (!$student) {
            $student = User::where('role', 'student')
                ->whereNotIn('user_id', self::PRODUCT_FREE_TEST_USERS)
                ->first();
        }

        if (!$teacher) {
            $teacher = User::first();
        }

        $submittedAt = now()->subDays(rand(10, 60));
        $approvedAt = (clone $submittedAt)->addDays(rand(1, 14));

        $status = $this->getRandomStatus();

        $approve_by = ($status === 'pending') ? null : $teacher->user_id;

        return Product::create([
            'title' => $title,
            'description' => $description,
            'thumbnail' => $thumbnail,
            'status' => $this->getRandomStatus(),
            'user_id' => $student->user_id,
            'major_id' => $majorId,
            'cate_id' => Category::inRandomOrder()->value('cate_id'),
            'approved_by' =>  $approve_by,
            'submitted_at' => $submittedAt,
            'approved_at' => $approvedAt,
            'github_link' => 'https://demo.tdc.edu.vn/github-link',
            'demo_link' => 'https://demo.tdc.edu.vn/demo-link',
        ]);
    }
}
