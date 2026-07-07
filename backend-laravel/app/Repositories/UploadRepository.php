<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Models\User;
use App\Repositories\MajorRepository;
use Carbon\Carbon;
use App\Http\Common\NormalizeMajorCode;
use App\Models\ProductAi;
use App\Models\ProductCNTT;
use App\Models\ProductGraphic;
use App\Models\ProductMMT;
use Illuminate\Support\Facades\DB;

use function Symfony\Component\Clock\now;

class UploadRepository extends BaseRepository
{

    public function __construct(
        protected MajorRepository $major_repository,
        protected NormalizeMajorCode $normalizeMajorCode
    ) {}

    public function countPublishedProducts(): ?int
    {
        $userId = $this->getCurrentUserId();
        return User::query()
            ->join('products', 'users.user_id', '=', 'products.user_id')
            ->where('products.status', 'approved')
            ->where('users.user_id', $userId)->count();
    }

    /**
     * Upload product và liên quan
     */
    public function upload(array $data, array $uploadedImages, array $tags, int $thumbnailIndex = 0): Product
    {
        return DB::transaction(function () use ($data, $uploadedImages, $tags, $thumbnailIndex) {

            if (! empty($data['replace_product_id'])) {
                Product::where('product_id', $data['replace_product_id'])
                    ->where('user_id', $this->getCurrentUserId())
                    ->delete();
            }

            $teamMembers = array_values(array_filter(array_map(
                'trim',
                preg_split('/\r\n|\r|\n/', (string) ($data['team_members'] ?? ''))
            )));

            $thumbnail = $uploadedImages[$thumbnailIndex] ?? ($uploadedImages[0] ?? null);
            $otherImages = array_values(array_filter(
                $uploadedImages,
                fn($imageUrl, $index) => $index !== $thumbnailIndex,
                ARRAY_FILTER_USE_BOTH
            ));

            $product = Product::create([
                'title' => $data['title'],
                'description' => $data['description'] ?? null,
                'team_members' => $teamMembers ?: null,
                'thumbnail' => $thumbnail,
                'status' => 'pending',
                'user_id' => $this->getCurrentUserId(),
                'major_id' => $data['major_id'],
                'cate_id' => $data['cate_id'],
                'github_link' => $data['github_link'] ?? null,
                'demo_link' => $data['demo_link'] ?? null,
                'approved_by' => null,
                'advisor_name' => $data['advisor_name'] ?? null,
                'video_url' => $data['video_url'] ?? null,
                'submitted_at' => Carbon::now(),
                'approved_at' => null,
            ]);

            if (!isset($data['major_code'])) {
                throw new \Exception("major_code is required");
            }

            $majorCode = $this->normalizeMajorCode->NormalizeMajorCode($data['major_code']);

            switch ($majorCode) {
                case 'ai':
                    ProductAi::create([
                        'product_id' => $product->product_id,
                        'model_used' => $data['model_used'] ?? null,
                        'framework' => $data['framework'] ?? null,
                        'language' => $data['language'] ?? null,
                        'dataset_used' => $data['dataset_used'] ?? null,
                        'accuracy_score' => $data['accuracy_score'] ?? null,
                    ]);
                    break;

                case 'cntt':
                    ProductCNTT::create([
                        'product_id' => $product->product_id,
                        'programming_language' => $data['programming_language'] ?? null,
                        'framework' => $data['framework'] ?? null,
                        'database_used' => $data['database_used'] ?? null,
                    ]);
                    break;

                case 'mmt':
                    ProductMMT::create([
                        'product_id' => $product->product_id,
                        'simulation_tool' => $data['simulation_tool'] ?? null,
                        'network_protocol' => $data['network_protocol'] ?? null,
                        'topology_type' => $data['topology_type'] ?? null,
                        'config_file' => $data['config_file'] ?? null,
                    ]);
                    break;

                case 'tkdh':
                    $palette = array_slice(array_values(array_unique(array_filter(array_map(
                        fn (string $color) => strtoupper(trim($color)),
                        preg_split('/\s*,\s*/', (string) ($data['color_palette'] ?? ''))
                    ), fn (string $color) => preg_match('/^#[0-9A-F]{6}$/', $color) === 1))), 0, 8);

                    ProductGraphic::create([
                        'product_id' => $product->product_id,
                        'design_type' => $data['design_type'] ?? null,
                        'tools_used' => $data['tools_used'] ?? null,
                        'color_palette' => $palette ?: null,
                        'behance_link' => $data['behance_link'] ?? null,
                    ]);
                    break;

                default:
                    throw new \Exception("Invalid major_code");
            }

            foreach ($otherImages as $imageUrl) {
                ProductImage::create([
                    'product_id' => $product->product_id,
                    'image_url' => $imageUrl,
                ]);
            }

            foreach ($tags as $tagName) {
                ProductTag::create([
                    'product_id' => $product->product_id,
                    'tag_name' => $tagName,
                ]);
            }

            return $product;
        });
    }
}
