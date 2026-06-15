<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Services\SystemSettingService;
use Illuminate\Http\Request;

class SystemSettingController extends Controller
{
    public function __construct(
        protected SystemSettingService $settings
    ) {}

    public function index()
    {
        return response()->json([
            'success' => true,
            'data' => $this->settings->all(),
        ]);
    }

    public function update(Request $request)
    {
        $validated = $request->validate([
            SystemSettingService::AI_CHATBOX => ['sometimes', 'boolean'],
            SystemSettingService::AI_PRODUCT_CHECK => ['sometimes', 'boolean'],
            SystemSettingService::AI_SEARCH => ['sometimes', 'boolean'],
            SystemSettingService::AI_DASHBOARD_INSIGHTS => ['sometimes', 'boolean'],
            SystemSettingService::PRODUCT_SEARCH => ['sometimes', 'boolean'],
        ]);

        return response()->json([
            'success' => true,
            'message' => 'System settings updated.',
            'data' => $this->settings->update($validated),
        ]);
    }
}
