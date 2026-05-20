<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
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
                'message' => "Đã sảy ra lỗi!",
                'teacher_result' => false
            ]);
        }

        return response()->json([
            'data' => $return,
            'teacher_result' => true
        ]);
    }

    public function getTeacherData()
    {
        $return = $this->teacherService->showTeacherData();

        if (
            $return['pending_result']->isEmpty() &&
            $return['approved_result']->isEmpty() &&
            $return['rejected_result']->isEmpty()
        ) {
            return response()->json([
                'message' => 'không thể tải data!',
                'teacher_data_result' => false
            ], 404);
        }

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

    public function teacherReject(Request $request)
    {
        $status = 'rejected';
        try {
            $teacher_reject = $this->teacherService->updateStatus($request->product_id, $status, $request->feedback);

            return response()->json(
                $teacher_reject
            );
        } catch (\Exception $e) {
            return response()->json([
                'message' => $e->getMessage()
            ], 422);
        }
    }
}
