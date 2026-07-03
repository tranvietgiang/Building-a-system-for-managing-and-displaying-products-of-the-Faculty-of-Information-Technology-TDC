<?php

namespace Tests\Feature;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class VisitorEndpointEdgeTest extends TestCase
{
    use RefreshDatabase;

    public function test_visitor_products_default_pagination(): void
    {
        $response = $this->getJson('/api/v1/visitor/products');
        $response->assertOk();
        $response->assertJsonStructure([
            'message',
            'count',
            'products',
            'data',
            'stats',
        ]);
    }

    public function test_visitor_products_with_min_per_page(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?per_page=1');
        $response->assertOk();
    }

    public function test_visitor_products_with_max_per_page(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?per_page=100');
        $response->assertOk();
    }

    public function test_visitor_products_invalid_per_page_negative(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?per_page=-5');
        $response->assertStatus(422);
    }

    public function test_visitor_products_invalid_per_page_zero(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?per_page=0');
        $response->assertStatus(422);
    }

    public function test_visitor_products_invalid_per_page_non_numeric(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?per_page=abc');
        $response->assertStatus(422);
    }

    public function test_visitor_products_invalid_major_id_non_numeric(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?major_id=abc');
        $response->assertStatus(422);
    }

    public function test_visitor_products_invalid_sort_by(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?sort_by=unsupported');
        $response->assertStatus(422);
    }

    public function test_visitor_products_all_valid_sort_options(): void
    {
        $response = $this->getJson('/api/v1/visitor/products?sort_by=newest');
        $response->assertOk();

        $response = $this->getJson('/api/v1/visitor/products?sort_by=most_viewed');
        $response->assertOk();

        $response = $this->getJson('/api/v1/visitor/products?sort_by=most_liked');
        $response->assertOk();
    }

    public function test_visitor_majors_endpoint_returns_data(): void
    {
        $response = $this->getJson('/api/v1/visitor/majors');
        $response->assertOk();
    }

    public function test_visitor_search_empty_query(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?q=');
        $response->assertOk();
    }

    public function test_visitor_search_only_special_characters(): void
    {
        $response = $this->getJson('/api/v1/visitor/products/search?q=!@#$%^&*()');
        $response->assertOk();
    }

    public function test_non_existent_route_returns_404(): void
    {
        $response = $this->getJson('/api/v1/non-existent-route');
        $response->assertStatus(404);
    }

    public function test_wrong_http_method_on_existing_route(): void
    {
        $response = $this->putJson('/api/v1/visitor/products');
        $response->assertStatus(405);
    }

    public function test_post_to_get_route_returns_405(): void
    {
        $response = $this->postJson('/api/v1/visitor/majors');
        $response->assertStatus(405);
    }

    public function test_delete_on_visitor_product_returns_405(): void
    {
        $response = $this->deleteJson('/api/v1/visitor/product/1');
        $response->assertStatus(405);
    }

    public function test_increment_view_twice_same_product(): void
    {
        $response1 = $this->postJson('/api/v1/visitor/product/1/view');
        $response2 = $this->postJson('/api/v1/visitor/product/1/view');

        // Both should succeed regardless of existence
        $this->assertContains($response1->status(), [200, 404]);
        $this->assertContains($response2->status(), [200, 404]);
    }

    public function test_category_all_endpoint(): void
    {
        $response = $this->getJson('/api/v1/category/all');
        $response->assertOk();
    }

    public function test_system_settings_endpoint(): void
    {
        $response = $this->getJson('/api/v1/system-settings');
        $response->assertOk();
    }
}
