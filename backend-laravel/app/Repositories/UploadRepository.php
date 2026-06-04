<?php

namespace App\Repositories;

use App\Models\Product;
use App\Models\ProductFile;
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
    public function upload(array $data, array $uploadedImages, array $uploadedFiles, array $tags): Product
    {
        return DB::transaction(function () use ($data, $uploadedImages, $uploadedFiles, $tags) {

            $thumbnail = $uploadedImages[0] ?? null;
            $otherImages = array_slice($uploadedImages, 1);

            $product = Product::create([
                'title' => $data['title'],
                'description' => $data['description'],
                'thumbnail' => $thumbnail,
                'status' => 'pending',
                'user_id' => $this->getCurrentUserId(),
                'major_id' => $data['major_id'],
                'cate_id' => $data['cate_id'],
                'github_link' => $data['github_link'] ?? null,
                'demo_link' => $data['demo_link'] ?? null,
                'approved_by' => null,
                'submitted_at' => Carbon::now(),
                'approved_at' => Carbon::now(),
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
                    ProductGraphic::create([
                        'product_id' => $product->product_id,
                        'design_type' => $data['design_type'] ?? null,
                        'tools_used' => $data['tools_used'] ?? null,
                        'drive_link' => $data['drive_link'] ?? null,
                        'behance_link' => $data['behance_link'] ?? null,
                    ]);
                    break;

                default:
                    throw new \Exception("Invalid major_code");
            }

            foreach ($uploadedFiles as $fileUrl) {

                $extension = pathinfo($fileUrl, PATHINFO_EXTENSION);

                ProductFile::create([
                    'product_id' => $product->product_id,
                    'file_url' => $fileUrl,
                    'file_type' => $extension,
                ]);
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
