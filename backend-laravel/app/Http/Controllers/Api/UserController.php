<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;

class UserController extends Controller
{
    /**
     * Get current user profile
     */
    public function profile(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    /**
     * Get user by ID
     */
    public function show($userId)
    {
        $user = User::find($userId);

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Người dùng không tồn tại!',
            ], 404);
        }

        return response()->json([
            'success' => true,
            'user' => $user,
        ]);
    }

    /**
     * Update user profile
     */
    public function update(UserRequest $request)
    {
        $user = $request->user();
        $validated = $request->validated();

        // Chỉ cho phép cập nhật các trường này
        $allowedFields = ['name', 'email', 'phone', 'address', 'bio', 'mssv', 'class_name', 'avatar'];
        $updateData = array_intersect_key($validated, array_flip($allowedFields));

        try {
            $user->update($updateData);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật hồ sơ thành công!',
                'user' => $user,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật hồ sơ!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get user statistics (for students)
     */
    public function statistics(Request $request)
    {
        $user = $request->user();

        // Nếu không phải student thì return error
        if ($user->role !== 'student') {
            return response()->json([
                'success' => false,
                'message' => 'Chỉ sinh viên có thể xem thống kê!',
            ], 403);
        }

        $stats = [
            'total_products' => $user->products()->count(),
            'approved_products' => $user->products()->where('status', 'approved')->count(),
            'pending_products' => $user->products()->where('status', 'pending')->count(),
            'rejected_products' => $user->products()->where('status', 'rejected')->count(),
        ];

        return response()->json([
            'success' => true,
            'statistics' => $stats,
        ]);
    }

    /**
     * Update password
     */
    public function updatePassword(Request $request)
    {
        $request->validate([
            'current_password' => 'required|string|min:6',
            'new_password' => 'required|string|min:6|different:current_password',
            'password_confirmation' => 'required|same:new_password',
        ], [
            'new_password.different' => 'Mật khẩu mới phải khác mật khẩu hiện tại!',
            'password_confirmation.same' => 'Xác nhận mật khẩu không khớp!',
        ]);

        $user = $request->user();

        // Kiểm tra mật khẩu hiện tại
        if (!Hash::check($request->current_password, $user->password)) {
            return response()->json([
                'success' => false,
                'message' => 'Mật khẩu hiện tại không chính xác!',
            ], 401);
        }

        try {
            $user->update([
                'password' => Hash::make($request->new_password),
            ]);

            return response()->json([
                'success' => true,
                'message' => 'Cập nhật mật khẩu thành công!',
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Có lỗi xảy ra khi cập nhật mật khẩu!',
                'error' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Search users (for admin/teacher)
     */
    public function search(Request $request)
    {
        $user = $request->user();

        // Chỉ admin/teacher mới được search
        if (!in_array($user->role, ['admin', 'teacher'])) {
            return response()->json([
                'success' => false,
                'message' => 'Bạn không có quyền truy cập!',
            ], 403);
        }

        $query = $request->query('q', '');
        $role = $request->query('role');
        $major_id = $request->query('major_id');

        $users = User::query();

        if ($query) {
            $users->where('name', 'like', "%$query%")
                ->orWhere('email', 'like', "%$query%")
                ->orWhere('user_id', 'like', "%$query%");
        }

        if ($role) {
            $users->where('role', $role);
        }

        if ($major_id) {
            $users->where('major_id', $major_id);
        }

        $results = $users->limit(20)->get();

        return response()->json([
            'success' => true,
            'users' => $results,
        ]);
    }
}
