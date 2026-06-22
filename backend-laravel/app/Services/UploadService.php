<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\UploadRepository;
use App\Http\Ai\CheckImage;
use Illuminate\Support\Facades\DB;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Log;
use App\Models\Product;
use App\Http\Common\NormalizeMajorCode;
use App\Http\Ai\ContentModeration;

class UploadService extends BaseRepository
{
    public function __construct(
        protected UploadRepository $upload_repository,
        protected CheckImage $Check_ai_image,
        protected NormalizeMajorCode $normalizeMajorCode,
        protected ContentModeration $contentModeration,
        protected ProductDuplicateService $productDuplicateService
    ) {}



    /**
     * Xử lý upload + check AI + lưu DB
     */
    public function upload(array $data)
    {
        $replaceProductId = (int) ($data['replace_product_id'] ?? 0);

        if ($replaceProductId && ! Product::where('product_id', $replaceProductId)
            ->where('user_id', $this->getCurrentUserId())
            ->exists()) {
            return [
                'error' => true,
                'message' => 'Không tìm thấy sản phẩm cần chỉnh sửa hoặc bạn không có quyền chỉnh sửa.',
            ];
        }

        $duplicate = $this->productDuplicateService->check($data);

        if ($duplicate) {
            return [
                'error' => true,
                'message' => 'Sản phẩm bị trùng với “'.$duplicate['title'].'” ('.$duplicate['similarity'].'%).',
                'detail' => $duplicate,
            ];
        }

        $uploadedImages = array_values($data['existing_images'] ?? []);
        $tags = $data['tags'] ?? [];
        $existingThumbnailUrl = $data['existing_thumbnail_url'] ?? null;
        $thumbnailIndex = $existingThumbnailUrl
            ? (int) (array_search($existingThumbnailUrl, $uploadedImages, true) ?: 0)
            : 0;

        DB::beginTransaction();

        try {

            $cloudinary = new CloudinaryService();

            foreach (($data['images'] ?? []) as $index => $image) {

                $result = $this->contentModeration->moderateUploadedImage($image, [
                    'title' => $data['title'] ?? '',
                    'description' => $data['description'] ?? '',
                    'major' => $data['major_code'] ?? '',
                ]);

                Log::info([
                    'index' => $index,
                    'score' => $result['score'] ?? null,
                    'suggestive' => $result['suggestive'] ?? null,
                ]);

                if (empty($result['approved'])) {
                    DB::rollBack();

                    return [
                        'error' => true,
                        'message' => 'Ảnh thứ ' . ($index + 1) . ' không đạt kiểm duyệt: ' . ($result['reason'] ?? 'Nội dung không phù hợp'),
                        'image_index' => $index,
                        'detail' => $result
                    ];
                }
            }

            $newThumbnailOffset = count($uploadedImages);
            $newThumbnailIndex = $this->resolveThumbnailIndex($data['image_meta'] ?? []);

            foreach (($data['images'] ?? []) as $image) {
                $url = $cloudinary->upload($image);
                $uploadedImages[] = $url;
            }

            if (! $existingThumbnailUrl && ($data['images'] ?? []) !== []) {
                $thumbnailIndex = $newThumbnailOffset + $newThumbnailIndex;
            }


            $tags = array_filter($data['tags'] ?? []);

            $dbData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'team_members' => $data['team_members'] ?? null,
                'user_id' => $this->getCurrentUserId(),
                'cate_id' => $data['cate_id'],
                'major_id' => $data['major_id'],
                'major_code' => $data['major_code'] ?? null,
                'advisor_name' => $data['advisor_name'] ?? null,
                'replace_product_id' => $replaceProductId ?: null,
                'github_link' => $data['github_link'] ?? null,
                'demo_link' => $data['demo_link'] ?? null,
            ];


            $majorCode = $this->normalizeMajorCode->NormalizeMajorCode($data['major_code'] ?? null);

            switch ($majorCode) {
                case 'ai':
                    $dbData['model_used'] = $data['model_used'] ?? null;
                    $dbData['framework'] = $data['framework'] ?? null;
                    $dbData['language'] = $data['language'] ?? null;
                    $dbData['dataset_used'] = $data['dataset_used'] ?? null;
                    $dbData['accuracy_score'] = $data['accuracy_score'] ?? null;
                    break;

                case 'cntt':
                    $dbData['programming_language'] = $data['programming_language'] ?? null;
                    $dbData['framework'] = $data['framework'] ?? null;
                    $dbData['database_used'] = $data['database_used'] ?? null;
                    break;

                case 'mmt':
                    $dbData['simulation_tool'] = $data['simulation_tool'] ?? null;
                    $dbData['network_protocol'] = trim((string) ($data['network_protocol'] ?? ''))
                        ?: (implode(', ', $tags) ?: null);
                    $dbData['topology_type'] = $data['topology_type'] ?? null;
                    $dbData['config_file'] = $data['config_file'] ?? null;
                    break;

                case 'tkdh':
                    $dbData['design_type'] = $data['design_type'] ?? null;
                    $dbData['tools_used'] = trim((string) ($data['tools_used'] ?? ''))
                        ?: (implode(', ', $tags) ?: null);
                    $dbData['color_palette'] = $data['color_palette'] ?? null;
                    $dbData['behance_link'] = $data['behance_link'] ?? null;
                    break;

                default:
            }

            $product = $this->upload_repository->upload(
                $dbData,
                $uploadedImages,
                $tags,
                $thumbnailIndex
            );

            $product->thumbnail = $uploadedImages[$thumbnailIndex] ?? ($uploadedImages[0] ?? null);
            $product->images = $uploadedImages;

            DB::commit();

            return $product;
        } catch (\Exception $e) {

            DB::rollBack();

            throw $e;
        }
    }

    public function countPublishedProducts(): ?int
    {
        return $this->upload_repository->countPublishedProducts();
    }

    private function resolveThumbnailIndex(array $imageMeta): int
    {
        foreach ($imageMeta as $index => $meta) {
            $decoded = is_string($meta) ? json_decode($meta, true) : $meta;

            if (is_array($decoded) && ($decoded['is_thumbnail'] ?? false)) {
                return (int) $index;
            }
        }

        return 0;
    }
}
