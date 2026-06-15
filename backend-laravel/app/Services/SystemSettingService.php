<?php

namespace App\Services;

use App\Models\SystemSetting;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\Cache;

class SystemSettingService
{
    public const AI_CHATBOX = 'ai_chatbox_enabled';
    public const AI_PRODUCT_CHECK = 'ai_product_check_enabled';
    public const AI_SEARCH = 'ai_search_enabled';
    public const AI_DASHBOARD_INSIGHTS = 'ai_dashboard_insights_enabled';
    public const PRODUCT_SEARCH = 'product_search_enabled';

    public const DEFAULTS = [
        self::AI_CHATBOX => true,
        self::AI_PRODUCT_CHECK => true,
        self::AI_SEARCH => true,
        self::AI_DASHBOARD_INSIGHTS => true,
        self::PRODUCT_SEARCH => true,
    ];

    public function all(): array
    {
        return Cache::remember('system_settings', 60, function () {
            try {
                $stored = SystemSetting::query()
                    ->whereIn('key', array_keys(self::DEFAULTS))
                    ->pluck('value', 'key')
                    ->all();

                return collect(self::DEFAULTS)
                    ->mapWithKeys(fn ($value, $key) => [$key => (bool) ($stored[$key] ?? $value)])
                    ->all();
            } catch (QueryException) {
                return self::DEFAULTS;
            }
        });
    }

    public function enabled(string $key): bool
    {
        return (bool) ($this->all()[$key] ?? true);
    }

    public function update(array $values): array
    {
        foreach (self::DEFAULTS as $key => $default) {
            if (!array_key_exists($key, $values)) {
                continue;
            }

            SystemSetting::query()->updateOrCreate(
                ['key' => $key],
                ['value' => (bool) $values[$key]]
            );
        }

        Cache::forget('system_settings');

        return $this->all();
    }
}
