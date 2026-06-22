<?php

namespace App\Services;

use App\Repositories\BaseRepository;
use App\Repositories\ProductRepository;
use App\Repositories\TeacherRepository;
use App\Repositories\UserRepository;
use App\Repositories\ReviewRepository;
use Illuminate\Support\Collection;
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
        protected ReviewRepository $review_repo
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

    public function showTeacherDataPaginated(string $status = 'pending', int $perPage = 6): array
    {
        return [
            'products' => $this->product_repo->teacherProductsByStatus($status, $perPage),
            'counts' => $this->product_repo->teacherStatusCounts(),
        ];
    }

    public function updateStatus($product_id, $status, $feedback = null): array
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

        // xử lý theo status
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

        // nếu reject thì lưu feedback
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

    public function addReview(int $productId, string $comment): array
    {
        $user = request()->user();
        $product = Product::query()->find($productId);

        if (!$product) {
            return [
                'result' => false,
                'status' => 404,
                'message' => 'Sản phẩm không tồn tại.',
            ];
        }

        if ($user->role !== 'admin' && (int) $user->major_id !== (int) $product->major_id) {
            return [
                'result' => false,
                'status' => 403,
                'message' => 'Bạn không có quyền nhận xét sản phẩm thuộc chuyên ngành khác.',
            ];
        }

        $review = $this->review_repo->create([
            'product_id' => $productId,
            'teacher_id' => $user->user_id,
            'comment' => trim($comment),
        ]);
        $review->load('teacher:user_id,name,email,role');

        return [
            'result' => true,
            'status' => 201,
            'message' => 'Đã gửi nhận xét thành công.',
            'data' => [
                'review_id' => $review->review_id,
                'teacher_id' => $review->teacher_id,
                'comment' => $review->comment,
                'created_at' => $review->created_at,
                'teacher' => [
                    'user_id' => $review->teacher?->user_id,
                    'fullname' => $review->teacher?->name,
                    'email' => $review->teacher?->email,
                    'role' => $review->teacher?->role,
                ],
            ],
        ];
    }
}
