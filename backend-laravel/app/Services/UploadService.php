<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\UploadRepository;
use App\Http\Ai\CheckImage;
use Illuminate\Support\Facades\DB;
use App\Services\CloudinaryService;
use Illuminate\Support\Facades\Log;
use App\Http\Common\NormalizeMajorCode;
use App\Http\Ai\ContentModeration;

class UploadService extends BaseRepository
{
    public function __construct(
        protected UploadRepository $upload_repository,
        protected CheckImage $Check_ai_image,
        protected NormalizeMajorCode $normalizeMajorCode,
        protected ContentModeration $contentModeration
    ) {}



    /**
     * Xử lý upload + check AI + lưu DB
     */
    public function upload(array $data)
    {
        $uploadedImages = [];
        $uploadedFiles = [];
        $tags = $data['tags'] ?? [];

        DB::beginTransaction();

        try {

            $cloudinary = new CloudinaryService();

            foreach ($data['images'] as $index => $image) {

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
                        'message' => 'Ảnh vi phạm nội dung',
                        'detail' => $result
                    ];
                }
            }

            if (!empty($data['files'])) {
                foreach ($data['files'] as $file) {
                    $uploadedFiles[] = $file->store('uploads/files', 'public');
                }
            }

            foreach ($data['images'] as $image) {
                $url = $cloudinary->upload($image);
                $uploadedImages[] = $url;
            }


            $tags = array_filter($data['tags'] ?? []);

            $dbData = [
                'title' => $data['title'],
                'description' => $data['description'],
                'user_id' => $this->getCurrentUserId(),
                'cate_id' => $data['cate_id'],
                'major_id' => $data['major_id'],
                'major_code' => $data['major_code'] ?? null,
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
                    $dbData['network_protocol'] = $data['network_protocol'] ?? null;
                    $dbData['topology_type'] = $data['topology_type'] ?? null;
                    $dbData['config_file'] = isset($uploadedFiles[0]) ? $uploadedFiles[0] : null;
                    break;

                case 'tkdh':
                    $dbData['design_type'] = $data['design_type'] ?? null;
                    $dbData['tools_used'] = $data['tools_used'] ?? null;
                    break;

                default:
            }

            $product = $this->upload_repository->upload(
                $dbData,
                $uploadedImages,
                $uploadedFiles,
                $tags
            );

            $product->thumbnail = $uploadedImages[0] ?? null;
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
}
