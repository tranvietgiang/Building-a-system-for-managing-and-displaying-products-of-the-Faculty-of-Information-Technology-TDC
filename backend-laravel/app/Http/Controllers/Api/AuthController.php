<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\LoginRequest;
use App\Services\AuthService;
use Illuminate\Http\Request;
use App\Models\ActivityLog;
use App\Models\Support;
use App\Models\User;

class AuthController extends Controller
{
    public function __construct(protected AuthService $authService) {}

    public function login(LoginRequest $request)
    {
        $result = $this->authService->login($request->validated());

        if (!$result['success']) {
            return response()->json($result, $result['status'] ?? 422);
        }

        return response()->json($result);
    }

    public function logout(Request $rq)
    {
        $user = $rq->user();

        // Log the logout action
        ActivityLog::create([
            'user_id' => $user->user_id,
            'action' => 'logout',
            'ip_address' => $rq->ip(),
        ]);

        $user->currentAccessToken()->delete();

        return response()->json([
            'success' => true,
            'message' => 'Đăng xuất thành công!'
        ]);
    }

    public function me(Request $request)
    {
        return response()->json([
            'success' => true,
            'user' => $request->user(),
        ]);
    }

    public function submitPasswordRecovery(Request $request)
    {
        $validated = $request->validate([
            'identifier' => ['required', 'string', 'max:255'],
        ]);

        $identifier = trim($validated['identifier']);
        $user = User::query()
            ->where('email', $identifier)
            ->orWhere('user_id', $identifier)
            ->first();

        $support = Support::create([
            'identifier' => $identifier,
            'user_id' => $user?->user_id,
            'name' => $user?->name,
            'email' => $user?->email ?: (filter_var($identifier, FILTER_VALIDATE_EMAIL) ? $identifier : null),
            'type' => 'password_recovery',
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Password recovery request submitted.',
            'data' => [
                'support_id' => $support->support_id,
                'status' => $support->status,
            ],
        ], 201);
    }

    public function submitContact(Request $request)
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'email', 'max:255'],
            'phone' => ['nullable', 'string', 'max:30'],
            'subject' => ['required', 'string', 'max:255'],
            'message' => ['required', 'string', 'max:2000'],
        ]);

        $support = Support::create([
            'identifier' => $validated['email'],
            'name' => trim($validated['name']),
            'email' => trim($validated['email']),
            'phone' => $validated['phone'] ?? null,
            'subject' => trim($validated['subject']),
            'message' => trim($validated['message']),
            'type' => 'contact',
            'status' => 'pending',
        ]);

        return response()->json([
            'success' => true,
            'message' => 'Contact request submitted.',
            'data' => [
                'support_id' => $support->support_id,
                'status' => $support->status,
            ],
        ], 201);
    }
}
