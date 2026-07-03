<?php

namespace Tests\Unit;

use App\Services\ProductDuplicateService;
use PHPUnit\Framework\TestCase;

class ProductDuplicateEdgeTest extends TestCase
{
    private ProductDuplicateService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new ProductDuplicateService();
    }

    public function test_empty_data_returns_null(): void
    {
        $result = $this->invokeCheck([]);
        $this->assertNull($result);
    }

    public function test_zero_major_id_returns_null(): void
    {
        $result = $this->invokeCheck(['major_id' => 0, 'title' => 'test']);
        $this->assertNull($result);
    }

    public function test_empty_title_returns_null(): void
    {
        $result = $this->invokeCheck(['major_id' => 1, 'title' => '']);
        $this->assertNull($result);
    }

    public function test_title_only_whitespace_returns_null(): void
    {
        $result = $this->invokeCheck(['major_id' => 1, 'title' => '   ']);
        $this->assertNull($result);
    }

    public function test_negative_major_id_returns_null(): void
    {
        $result = $this->invokeCheck(['major_id' => -1, 'title' => 'test']);
        $this->assertNull($result);
    }

    public function test_null_title_returns_null(): void
    {
        $result = $this->invokeCheck(['major_id' => 1, 'title' => null]);
        $this->assertNull($result);
    }

    public function test_check_with_absent_keys_does_not_crash(): void
    {
        $result = $this->invokeCheck(['major_id' => 0, 'replace_product_id' => 'abc']);
        $this->assertNull($result);
    }

    public function test_text_similarity_both_empty_returns_zero(): void
    {
        $percent = $this->invokeTextSimilarity('', '');
        $this->assertSame(0, $percent);
    }

    public function test_text_similarity_one_empty_returns_zero(): void
    {
        $percent1 = $this->invokeTextSimilarity('hello', '');
        $percent2 = $this->invokeTextSimilarity('', 'hello');
        $this->assertSame(0, $percent1);
        $this->assertSame(0, $percent2);
    }

    public function test_text_similarity_null_values_returns_zero(): void
    {
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('textSimilarityPercent');
        $method->setAccessible(true);

        // Passing nulls should not crash
        $result = $method->invoke($this->service, '', '');
        $this->assertIsInt($result);
    }

    public function test_text_similarity_identical_texts(): void
    {
        $text = 'Đồ án phát triển ứng dụng web bán hàng trực tuyến';
        $percent = $this->invokeTextSimilarity($text, $text);
        $this->assertSame(100, $percent);
    }

    public function test_text_similarity_completely_different(): void
    {
        $percent = $this->invokeTextSimilarity('abc', 'xyz');
        $this->assertLessThan(50, $percent);
    }

    public function test_text_similarity_with_special_characters(): void
    {
        $percent = $this->invokeTextSimilarity(
            'hello! @world #2024',
            'hello world'
        );
        $this->assertGreaterThan(0, $percent);
    }

    public function test_text_similarity_very_long_strings(): void
    {
        $long1 = str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit ', 100);
        $long2 = str_repeat('Lorem ipsum dolor sit amet consectetur adipiscing elit ', 100);
        $percent = $this->invokeTextSimilarity($long1, $long2);
        $this->assertSame(100, $percent);
    }

    public function test_text_similarity_unicode_vietnamese(): void
    {
        $percent = $this->invokeTextSimilarity(
            'Hệ thống phát hiện xâm nhập mạng sử dụng AI',
            'Hệ thống phát hiện và ngăn chặn xâm nhập mạng sử dụng AI'
        );
        $this->assertGreaterThan(0, $percent);
        $this->assertLessThan(100, $percent);
    }

    public function test_text_similarity_html_tags_stripped(): void
    {
        $percent = $this->invokeTextSimilarity(
            '<p>Hello World</p>',
            'Hello World'
        );
        $this->assertSame(100, $percent);
    }

    public function test_normalize_text_multiple_spaces_collapsed(): void
    {
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('normalizeText');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, 'hello    world   test');
        $this->assertSame('hello world test', $result);
    }

    public function test_normalize_text_leading_trailing_spaces_removed(): void
    {
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('normalizeText');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, '  hello world  ');
        $this->assertSame('hello world', $result);
    }

    public function test_compare_data_missing_keys_graceful(): void
    {
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('comparableData');
        $method->setAccessible(true);

        $result = $method->invoke($this->service, ['title' => 'test']);
        $this->assertArrayHasKey('title', $result);
        $this->assertArrayNotHasKey('framework', [
            'framework' => null,
        ]);
    }

    private function invokeCheck(array $data): ?array
    {
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('check');
        $method->setAccessible(true);

        return $method->invoke($this->service, $data);
    }

    private function invokeTextSimilarity(string $a, string $b): int
    {
        $reflector = new \ReflectionClass($this->service);
        $method = $reflector->getMethod('textSimilarityPercent');
        $method->setAccessible(true);

        return $method->invoke($this->service, $a, $b);
    }
}
