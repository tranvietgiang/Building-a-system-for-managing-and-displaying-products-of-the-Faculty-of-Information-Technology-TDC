<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Support\Collection;
use App\Http\Ai\ContentModeration;
use App\Models\Product;
use App\Repositories\common\CommonRepository as RepositoriesCommonRepository;
use Carbon\Carbon;


class TeacherService extends BaseRepository
{
    public function __construct(
        protected TeacherRepository $teacherRepository,
        protected ProductRepository $product_repo,
        protected UserRepository $user_repository,
        protected RepositoriesCommonRepository $common_repository,
        protected ReviewRepository $review_repo,
        protected ContentModeration $contentModerationService
    ) {}



    public function teacherStatistic(): ?array
    {
        $totalProduct = $this->teacherRepository->productStatistic();
        $totalRejectedProduct = $this->teacherRepository->rejectedStatistic();

        $data = [];

        if ($totalProduct !== null) {
            $data['total_product'] = $totalProduct;
        }

        if ($totalRejectedProduct !== null) {
            $data['total_rejectedProduct'] = $totalRejectedProduct;
        }

        return $data;
    }


    public function showTeacherData(): Collection
    {
        $products = $this->product_repo->teacherAllData();

        return collect([
            'pending_result' => $products->where('status', 'pending')->values(),
            'approved_result' => $products->where('status', 'approved')->values(),
            'rejected_result' => $products->where('status', 'rejected')->values(),
        ]);
    }

    public function updateStatus($product_id, $status, $feedback = null, array $moderationContext = []): array
    {
        $productId = (int) $product_id;
        $userId = $this->getCurrentUserId();

        $product = $this->product_repo->findByIdPAndIdMajor(
            $productId,
            $this->common_repository->getMajorId()
        );

        if (!$product) {
            return [
                'result' => false,
                'message' => 'Sản phẩm không tồn tại!'
            ];
        }

        if ($product->status !== 'pending') {
            return [
                'result' => false,
                'message' => 'Sản phẩm không chờ duyệt!'
            ];
        }

        $now = Product::where('product_id', $product_id)->first();

        if ($status === 'approved') {
            // Skip AI moderation if force_approve is true
            $forceApprove = $moderationContext['force_approve'] ?? false;

            if (!$forceApprove) {
                $moderation = $this->contentModerationService->moderateProduct($product, $moderationContext);

                if (!$moderation['approved']) {
                    return [
                        'result' => false,
                        'blocked_by_ai' => true,
                        'message' => 'AI đã chặn duyệt sản phẩm: ' . $moderation['reason'],
                        'reason' => $moderation['reason'],
                        'violations' => $moderation['violations'] ?? [],
                        'moderation' => $moderation,
                    ];
                }
            }
        }


        // 👉 xử lý theo status
        if ($status === 'approved') {
            $data = [
                'status' => 'approved',
                'approved_by' => $userId,
                'approved_at' => now(),
            ];
        } else if ($status === 'rejected') {
            $data = [
                'status' => 'rejected',
                'approved_by' => null,
                'approved_at' => now(),    // ✅ lưu thời điểm xử lý

            ];
        }

        $this->product_repo->update($product, $data);

        // 👉 nếu reject thì lưu feedback
        if ($status === 'rejected' && $feedback) {
            $this->review_repo->create([
                'product_id' => $productId,
                'teacher_id' => $userId,
                'comment' => $feedback
            ]);
        }


        return [
            'result' => true,
            'message' => $status === 'approved'
                ? 'Duyệt thành công!'
                : 'Từ chối thành công!',
            'data' => $product
        ];
    }
}
