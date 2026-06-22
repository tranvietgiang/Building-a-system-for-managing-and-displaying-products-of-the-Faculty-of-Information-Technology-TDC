<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\RejectProductRequest;
use App\Http\Requests\StoreReviewRequest;
use App\Services\TeacherService;
use Illuminate\Http\Request;

class TeacherController extends Controller
{

    public function __construct(
        protected TeacherService $teacherService
    ) {}

    public function getTeacherStatistic()
    {
        $return = $this->teacherService->teacherStatistic();

        if (!$return) {
            return response()->json([
                'message' => "Đã xảy ra lỗi!",
                'teacher_result' => false
            ]);
        }

        return response()->json([
            'data' => $return,
            'teacher_result' => true
        ]);
    }

    public function getTeacherData(Request $request)
    {
        $request->validate([
            'status' => ['nullable', 'in:pending,approved,rejected'],
            'per_page' => ['nullable', 'integer', 'min:1', 'max:100'],
        ]);

        $status = $request->query('status', 'pending');
        $perPage = (int) $request->query('per_page', 6);
        $perPage = max(1, min($perPage, 100));

        $return = $this->teacherService->showTeacherDataPaginated($status, $perPage);


        return response()->json([
            'data' => $return,
            'teacher_data_result' => true
        ]);
    }
    public function teacherApprove(Request $request, $product_id)
    {
        $status = 'approved';
        try {
            $teacher_approve = $this->teacherService->updateStatus(
                $product_id,
                $status,
                null,
                array_merge(
                    $request->only(['title', 'description', 'major', 'image', 'thumbnail']),
                    ['force_approve' => $request->boolean('force_approve', false)]
                )
            );

            return response()->json(
                $teacher_approve,
                ($teacher_approve['blocked_by_ai'] ?? false) ? 422 : 200
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function teacherReject(RejectProductRequest $request)
    {
        $status = 'rejected';
        try {
            $validated = $request->validated();
            $teacher_reject = $this->teacherService->updateStatus(
                $validated['product_id'],
                $status,
                $validated['feedback']
            );

            $statusCode = 200;
            if (!($teacher_reject['result'] ?? false)) {
                $message = (string) ($teacher_reject['message'] ?? '');
                $statusCode = str_contains($message, 'không tồn tại') ? 404 : 422;
            }

            return response()->json(
                $teacher_reject,
                $statusCode
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }

    public function storeReview(StoreReviewRequest $request, int $product_id)
    {
        $result = $this->teacherService->addReview(
            $product_id,
            $request->validated('comment')
        );

        return response()->json($result, $result['status'] ?? 200);
    }
}
