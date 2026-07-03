<?php

namespace Tests\Unit;

use App\Models\SystemSetting;
use App\Services\SystemSettingService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class SystemSettingEdgeTest extends TestCase
{
    use RefreshDatabase;

    private SystemSettingService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = app(SystemSettingService::class);
    }

    public function test_default_values_when_no_settings_in_database(): void
    {
        $settings = $this->service->all();
        $this->assertTrue($settings[SystemSettingService::AI_CHATBOX]);
        $this->assertTrue($settings[SystemSettingService::AI_PRODUCT_CHECK]);
        $this->assertTrue($settings[SystemSettingService::AI_SEARCH]);
        $this->assertTrue($settings[SystemSettingService::AI_DASHBOARD_INSIGHTS]);
        $this->assertTrue($settings[SystemSettingService::PRODUCT_SEARCH]);
    }

    public function test_non_existent_key_returns_true_by_default(): void
    {
        $this->assertTrue($this->service->enabled('non_existent_key'));
    }

    public function test_empty_array_update_does_not_change(): void
    {
        $before = $this->service->all();
        $after = $this->service->update([]);
        $this->assertSame($before, $after);
    }

    public function test_update_with_wrong_key_types_ignored(): void
    {
        $before = $this->service->all();
        $result = $this->service->update(['non_existent_key' => false]);
        $this->assertSame($before, $result);
    }

    public function test_cache_forget_on_update(): void
    {
        Cache::shouldReceive('remember')
            ->once()
            ->andReturn([SystemSettingService::AI_CHATBOX => true]);

        Cache::shouldReceive('forget')
            ->once()
            ->with('system_settings');

        $this->service->update([SystemSettingService::AI_CHATBOX => true]);
    }

    public function test_boolean_coercion_of_string_values(): void
    {
        $this->service->update([SystemSettingService::AI_CHATBOX => 'false']);
        $settings = $this->service->all();

        $this->assertFalse($settings[SystemSettingService::AI_CHATBOX]);
    }

    public function test_boolean_coercion_of_integer_values(): void
    {
        $this->service->update([SystemSettingService::AI_CHATBOX => 0]);
        $settings = $this->service->all();
        $this->assertFalse($settings[SystemSettingService::AI_CHATBOX]);

        $this->service->update([SystemSettingService::AI_CHATBOX => 1]);
        $settings = $this->service->all();
        $this->assertTrue($settings[SystemSettingService::AI_CHATBOX]);
    }

    public function test_constants_are_defined(): void
    {
        $this->assertNotEmpty(SystemSettingService::AI_CHATBOX);
        $this->assertNotEmpty(SystemSettingService::AI_PRODUCT_CHECK);
        $this->assertNotEmpty(SystemSettingService::AI_SEARCH);
        $this->assertNotEmpty(SystemSettingService::AI_DASHBOARD_INSIGHTS);
        $this->assertNotEmpty(SystemSettingService::PRODUCT_SEARCH);
    }

    public function test_defaults_constant_has_all_keys(): void
    {
        $this->assertArrayHasKey(SystemSettingService::AI_CHATBOX, SystemSettingService::DEFAULTS);
        $this->assertArrayHasKey(SystemSettingService::AI_PRODUCT_CHECK, SystemSettingService::DEFAULTS);
        $this->assertArrayHasKey(SystemSettingService::AI_SEARCH, SystemSettingService::DEFAULTS);
        $this->assertArrayHasKey(SystemSettingService::AI_DASHBOARD_INSIGHTS, SystemSettingService::DEFAULTS);
        $this->assertArrayHasKey(SystemSettingService::PRODUCT_SEARCH, SystemSettingService::DEFAULTS);
    }

    public function test_update_persists_to_database(): void
    {
        $this->service->update([SystemSettingService::AI_CHATBOX => false]);

        $this->assertDatabaseHas('system_settings', [
            'key' => SystemSettingService::AI_CHATBOX,
            'value' => 0,
        ]);
    }

    public function test_enabled_reflects_database_value(): void
    {
        SystemSetting::query()->updateOrCreate(
            ['key' => SystemSettingService::AI_CHATBOX],
            ['value' => false]
        );

        Cache::forget('system_settings');

        $this->assertFalse($this->service->enabled(SystemSettingService::AI_CHATBOX));
    }
}
