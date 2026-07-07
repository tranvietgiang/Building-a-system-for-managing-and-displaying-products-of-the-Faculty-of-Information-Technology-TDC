<?php

namespace Tests\Unit;

use App\Models\User;
use App\Models\Product;
use App\Models\Major;
use App\Models\Category;
use App\Models\Review;
use App\Models\RefreshToken;
use App\Models\SystemSetting;
use App\Models\ActivityLog;
use App\Models\Support;
use App\Models\ProductStatistic;
use App\Models\ProductImage;
use App\Models\ProductTag;
use App\Models\ProductAi;
use App\Models\ProductCNTT;
use App\Models\ProductMMT;
use App\Models\ProductGraphic;
use PHPUnit\Framework\TestCase;

class ModelFillableEdgeTest extends TestCase
{
    public function test_user_mass_assignment_protection(): void
    {
        $user = new User(['password' => 'should_be_hashed_via_mutator']);
        $this->assertNotNull($user->password);
        $this->assertNull($user->getAttribute('remember_token'));
    }

    public function test_user_hidden_attributes(): void
    {
        $user = new User();
        $hidden = $user->getHidden();
        $this->assertContains('password', $hidden);
        $this->assertContains('remember_token', $hidden);
    }

    public function test_user_primary_key_is_string_not_incrementing(): void
    {
        $user = new User();
        $this->assertSame('user_id', $user->getKeyName());
        $this->assertSame('string', $user->getKeyType());
        $this->assertFalse($user->getIncrementing());
    }

    public function test_product_casts_team_members_to_array(): void
    {
        $product = new Product();
        $casts = $product->getCasts();
        $this->assertArrayHasKey('team_members', $casts);
        $this->assertSame('array', $casts['team_members']);
    }

    public function test_product_primary_key_is_int_incrementing(): void
    {
        $product = new Product();
        $this->assertSame('product_id', $product->getKeyName());
        $this->assertSame('int', $product->getKeyType());
        $this->assertTrue($product->getIncrementing());
    }

    public function test_models_have_correct_table_names(): void
    {
        $this->assertSame('users', (new User())->getTable());
        $this->assertSame('products', (new Product())->getTable());
        $this->assertSame('majors', (new Major())->getTable());
        $this->assertSame('categories', (new Category())->getTable());
        $this->assertSame('reviews', (new Review())->getTable());
        $this->assertSame('refresh_tokens', (new RefreshToken())->getTable());
        $this->assertSame('system_settings', (new SystemSetting())->getTable());
        $this->assertSame('activity_logs', (new ActivityLog())->getTable());
        $this->assertSame('support', (new Support())->getTable());
        $this->assertSame('product_statistics', (new ProductStatistic())->getTable());
        $this->assertSame('product_images', (new ProductImage())->getTable());
        $this->assertSame('product_tags', (new ProductTag())->getTable());
        $this->assertSame('product_ai', (new ProductAi())->getTable());
        $this->assertSame('product_cntt', (new ProductCNTT())->getTable());
        $this->assertSame('product_mmt', (new ProductMMT())->getTable());
        $this->assertSame('product_graphic', (new ProductGraphic())->getTable());
    }

    public function test_refresh_token_casts_datetime(): void
    {
        $token = new RefreshToken();
        $casts = $token->getCasts();
        $this->assertArrayHasKey('expires_at', $casts);
        $this->assertArrayHasKey('revoked_at', $casts);
    }

    public function test_support_casts_processed_at(): void
    {
        $support = new Support();
        $casts = $support->getCasts();
        $this->assertArrayHasKey('processed_at', $casts);
    }

    public function test_system_setting_casts_value_as_boolean(): void
    {
        $setting = new SystemSetting();
        $casts = $setting->getCasts();
        $this->assertArrayHasKey('value', $casts);
        $this->assertSame('boolean', $casts['value']);
    }

    public function test_product_graphic_casts_color_palette(): void
    {
        $graphic = new ProductGraphic();
        $casts = $graphic->getCasts();
        $this->assertArrayHasKey('color_palette', $casts);
        $this->assertSame('array', $casts['color_palette']);
    }

    public function test_product_ai_casts_accuracy_score(): void
    {
        $ai = new ProductAi();
        $casts = $ai->getCasts();
        $this->assertArrayHasKey('accuracy_score', $casts);
        $this->assertSame('float', $casts['accuracy_score']);
    }

    public function test_user_fillable_contains_expected_fields(): void
    {
        $user = new User();
        $fillable = $user->getFillable();
        $this->assertContains('name', $fillable);
        $this->assertContains('email', $fillable);
        $this->assertContains('password', $fillable);
        $this->assertContains('role', $fillable);
        $this->assertContains('major_id', $fillable);
    }

    public function test_invalid_data_does_not_break_model(): void
    {
        $user = new User();
        $user->fill(['non_existent_column' => 'should be ignored']);
        $this->assertNull($user->getAttribute('non_existent_column'));
    }
}
