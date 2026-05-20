<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Models\Category;
use App\Models\Major;
use App\Models\Product;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\Rule;

class AdminController extends Controller
{
    public function dashboard()
    {
        $productsByStatus = Product::query()
            ->select('status', DB::raw('COUNT(*) as total'))
            ->groupBy('status')
            ->pluck('total', 'status');

        $usersByRole = User::query()
            ->select('role', DB::raw('COUNT(*) as total'))
            ->groupBy('role')
            ->pluck('total', 'role');

        $majors = Major::query()
            ->leftJoin('products', 'majors.major_id', '=', 'products.major_id')
            ->select(
                'majors.major_id',
                'majors.major_name',
                'majors.major_code',
                DB::raw('COUNT(products.product_id) as products_count')
            )
            ->groupBy('majors.major_id', 'majors.major_name', 'majors.major_code')
            ->orderByDesc('products_count')
            ->limit(6)
            ->get();

        $recentProducts = Product::query()
            ->leftJoin('users', 'products.user_id', '=', 'users.user_id')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->select(
                'products.product_id',
                'products.title',
                'products.status',
                'products.created_at',
                'users.name as student_name',
                'majors.major_name'
            )
            ->latest('products.created_at')
            ->limit(8)
            ->get();

        return response()->json([
            'success' => true,
            'data' => [
                'totals' => [
                    'users' => User::count(),
                    'students' => (int) ($usersByRole['student'] ?? 0),
                    'teachers' => (int) ($usersByRole['teacher'] ?? 0),
                    'admins' => (int) ($usersByRole['admin'] ?? 0),
                    'products' => Product::count(),
                    'approved_products' => (int) ($productsByStatus['approved'] ?? 0),
                    'pending_products' => (int) ($productsByStatus['pending'] ?? 0),
                    'rejected_products' => (int) ($productsByStatus['rejected'] ?? 0),
                    'majors' => Major::count(),
                    'categories' => Category::count(),
                ],
                'majors' => $majors,
                'recent_products' => $recentProducts,
            ],
        ]);
    }

    public function users(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);
        $query = trim((string) $request->query('q', ''));
        $role = $request->query('role');
        $majorId = $request->query('major_id');

        $users = User::query()
            ->leftJoin('majors', 'users.major_id', '=', 'majors.major_id')
            ->select('users.*', 'majors.major_name')
            ->when($query, function ($builder) use ($query) {
                $builder->where(function ($subQuery) use ($query) {
                    $subQuery
                        ->where('users.user_id', 'like', "%{$query}%")
                        ->orWhere('users.name', 'like', "%{$query}%")
                        ->orWhere('users.email', 'like', "%{$query}%");
                });
            })
            ->when($role, fn ($builder) => $builder->where('users.role', $role))
            ->when($majorId, fn ($builder) => $builder->where('users.major_id', $majorId))
            ->orderByDesc('users.created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $users,
        ]);
    }

    public function storeUser(Request $request)
    {
        $validated = $request->validate([
            'user_id' => ['required', 'string', 'max:15', 'unique:users,user_id'],
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', 'unique:users,email'],
            'password' => ['required', 'string', 'min:6'],
            'role' => ['required', Rule::in(['student', 'teacher', 'admin'])],
            'major_id' => ['nullable', 'exists:majors,major_id'],
            'class' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:500'],
        ]);

        $validated['password'] = Hash::make($validated['password']);

        $user = User::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tao nguoi dung thanh cong.',
            'data' => $user,
        ], 201);
    }

    public function updateUser(Request $request, string $userId)
    {
        $user = User::findOrFail($userId);

        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->user_id, 'user_id')],
            'password' => ['nullable', 'string', 'min:6'],
            'role' => ['required', Rule::in(['student', 'teacher', 'admin'])],
            'major_id' => ['nullable', 'exists:majors,major_id'],
            'class' => ['nullable', 'string', 'max:255'],
            'avatar' => ['nullable', 'string', 'max:500'],
        ]);

        if (!empty($validated['password'])) {
            $validated['password'] = Hash::make($validated['password']);
        } else {
            unset($validated['password']);
        }

        $user->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cap nhat nguoi dung thanh cong.',
            'data' => $user,
        ]);
    }

    public function destroyUser(Request $request, string $userId)
    {
        if ($request->user()?->user_id === $userId) {
            return response()->json([
                'success' => false,
                'message' => 'Khong the xoa tai khoan dang dang nhap.',
            ], 422);
        }

        User::findOrFail($userId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa nguoi dung thanh cong.',
        ]);
    }

    public function products(Request $request)
    {
        $perPage = min(max((int) $request->query('per_page', 12), 1), 100);
        $query = trim((string) $request->query('q', ''));
        $status = $request->query('status');
        $majorId = $request->query('major_id');

        $products = Product::query()
            ->leftJoin('users', 'products.user_id', '=', 'users.user_id')
            ->leftJoin('majors', 'products.major_id', '=', 'majors.major_id')
            ->leftJoin('categories', 'products.cate_id', '=', 'categories.cate_id')
            ->leftJoin('product_statistics', 'products.product_id', '=', 'product_statistics.product_id')
            ->select(
                'products.*',
                'users.name as student_name',
                'majors.major_name',
                'categories.category_name',
                DB::raw('COALESCE(product_statistics.views, 0) as views'),
                DB::raw('COALESCE(product_statistics.likes, 0) as likes')
            )
            ->when($query, fn ($builder) => $builder->where('products.title', 'like', "%{$query}%"))
            ->when($status, fn ($builder) => $builder->where('products.status', $status))
            ->when($majorId, fn ($builder) => $builder->where('products.major_id', $majorId))
            ->orderByDesc('products.created_at')
            ->paginate($perPage);

        return response()->json([
            'success' => true,
            'data' => $products,
        ]);
    }

    public function updateProductStatus(Request $request, int $productId)
    {
        $validated = $request->validate([
            'status' => ['required', Rule::in(['pending', 'approved', 'rejected'])],
        ]);

        $product = Product::findOrFail($productId);
        $product->status = $validated['status'];

        if ($validated['status'] === 'approved') {
            $product->approved_by = $request->user()->user_id;
            $product->approved_at = now();
        }

        $product->save();

        return response()->json([
            'success' => true,
            'message' => 'Cap nhat trang thai san pham thanh cong.',
            'data' => $product,
        ]);
    }

    public function destroyProduct(int $productId)
    {
        Product::findOrFail($productId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa san pham thanh cong.',
        ]);
    }

    public function majors()
    {
        $majors = Major::query()
            ->withCount('products')
            ->orderBy('major_name')
            ->get();

        return response()->json([
            'success' => true,
            'data' => $majors,
        ]);
    }

    public function storeMajor(Request $request)
    {
        $validated = $request->validate([
            'major_name' => ['required', 'string', 'max:255'],
            'major_code' => ['required', 'string', 'max:50', 'unique:majors,major_code'],
            'description' => ['nullable', 'string'],
        ]);

        $major = Major::create($validated);

        return response()->json([
            'success' => true,
            'message' => 'Tao chuyen nganh thanh cong.',
            'data' => $major,
        ], 201);
    }

    public function updateMajor(Request $request, int $majorId)
    {
        $major = Major::findOrFail($majorId);

        $validated = $request->validate([
            'major_name' => ['required', 'string', 'max:255'],
            'major_code' => ['required', 'string', 'max:50', Rule::unique('majors', 'major_code')->ignore($major->major_id, 'major_id')],
            'description' => ['nullable', 'string'],
        ]);

        $major->update($validated);

        return response()->json([
            'success' => true,
            'message' => 'Cap nhat chuyen nganh thanh cong.',
            'data' => $major,
        ]);
    }

    public function destroyMajor(int $majorId)
    {
        $hasProducts = Product::where('major_id', $majorId)->exists();
        $hasUsers = User::where('major_id', $majorId)->exists();

        if ($hasProducts || $hasUsers) {
            return response()->json([
                'success' => false,
                'message' => 'Khong the xoa chuyen nganh dang co nguoi dung hoac san pham.',
            ], 422);
        }

        Major::findOrFail($majorId)->delete();

        return response()->json([
            'success' => true,
            'message' => 'Xoa chuyen nganh thanh cong.',
        ]);
    }

    public function categories()
    {
        return response()->json([
            'success' => true,
            'data' => Category::orderBy('category_name')->get(),
        ]);
    }
}
