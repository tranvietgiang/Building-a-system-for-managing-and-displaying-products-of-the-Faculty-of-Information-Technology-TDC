<?php

namespace Tests\Unit;

use App\Http\Ai\ChatBoxAi;
use App\Http\Common\NormalizeMajorCode;
use App\Services\SystemSettingService;
use ReflectionClass;
use Tests\TestCase;

class ChatBoxAiUnitTest extends TestCase
{
    private ChatBoxAi $chat;
    private ReflectionClass $reflection;

    protected function setUp(): void
    {
        parent::setUp();

        $normalizer = $this->createMock(NormalizeMajorCode::class);
        $settings = $this->createMock(SystemSettingService::class);
        $settings->method('enabled')->willReturn(true);

        $this->chat = new ChatBoxAi($normalizer, $settings);
        $this->reflection = new ReflectionClass($this->chat);
    }

    private function invokePrivateMethod(string $name, array $args = []): mixed
    {
        $method = $this->reflection->getMethod($name);
        $method->setAccessible(true);
        return $method->invoke($this->chat, ...$args);
    }

    /* ─── isRelevantQuestion ─── */

    public function test_relevant_questions_return_true(): void
    {
        $relevant = [
            'Cho em xem đồ án',
            'Sản phẩm AI',
            'Tìm đồ án về machine learning',
            'Có đồ án CNTT nào không?',
            'Danh sách sản phẩm mạng máy tính',
            'cho tui xem sản phẩm thiết kế đồ họa',
            'Làm sao để nộp đồ án?',
            'Hướng dẫn upload sản phẩm',
            'Cách duyệt đồ án như thế nào?',
            'Top sản phẩm xem nhiều nhất',
        ];

        foreach ($relevant as $question) {
            $this->assertTrue(
                $this->invokePrivateMethod('isRelevantQuestion', [$question]),
                "Failed for: {$question}"
            );
        }
    }

    public function test_irrelevant_questions_return_false(): void
    {
        $irrelevant = [
            'Cảm ơn bạn nhiều',
            'Có chơi game không?',
            'Trường đại học nào?',
        ];

        foreach ($irrelevant as $question) {
            $this->assertFalse(
                $this->invokePrivateMethod('isRelevantQuestion', [$question]),
                "Failed for: {$question}"
            );
        }
    }

    /* ─── detectFeatureIntents ─── */

    public function test_detect_feature_intents(): void
    {
        $method = $this->reflection->getMethod('detectFeatureIntents');
        $method->setAccessible(true);

        $this->assertContains('search', $method->invoke($this->chat, 'Tìm đồ án AI'));
        $this->assertContains('compare', $method->invoke($this->chat, 'so sánh sản phẩm'));
        $this->assertContains('compare', $method->invoke($this->chat, 'kiểm tra trùng lặp'));
        $this->assertContains('image_check', $method->invoke($this->chat, 'kiểm tra hình ảnh'));
        $this->assertContains('technical_support', $method->invoke($this->chat, 'bị lỗi upload'));
        $this->assertContains('technical_support', $method->invoke($this->chat, 'bị lỗi không tải được'));
        $this->assertNotContains('technical_support', $method->invoke($this->chat, 'cho xem sản phẩm'));
    }

    /* ─── nonRelevantReplyBank ─── */

    public function test_non_relevant_reply_bank_has_all_categories(): void
    {
        $bank = $this->invokePrivateMethod('nonRelevantReplyBank');
        $this->assertArrayHasKey('greeting', $bank);
        $this->assertArrayHasKey('vague', $bank);
        $this->assertArrayHasKey('off_topic', $bank);
        $this->assertArrayHasKey('study_help', $bank);
        $this->assertArrayHasKey('technical', $bank);
        $this->assertArrayHasKey('capability', $bank);
    }

    public function test_non_relevant_reply_bank_has_sufficient_replies(): void
    {
        $bank = $this->invokePrivateMethod('nonRelevantReplyBank');
        $totalReplies = array_sum(array_map('count', $bank));
        $this->assertGreaterThanOrEqual(100, $totalReplies);
    }

    /* ─── buildNoLocalProductsReply ─── */

    public function test_no_local_products_reply_contains_topic_keywords(): void
    {
        $analysis = [
            'major_code' => 'AI',
            'major_name' => 'Trí tuệ nhân tạo',
            'terms' => ['machine learning', 'deep learning'],
        ];

        $reply = $this->invokePrivateMethod('buildNoLocalProductsReply', [$analysis]);
        $this->assertStringContainsString('machine learning', $reply);
    }

    /* ─── extractLocalSearchTerms ─── */

    public function test_extract_local_search_terms_vietnamese(): void
    {
        $method = $this->reflection->getMethod('extractLocalSearchTerms');
        $method->setAccessible(true);

        $terms = $method->invoke($this->chat, 'cho em xem đồ án máy học');
        $this->assertIsArray($terms);
        $this->assertNotEmpty($terms);
    }

    /* ─── buildRetrievedProductsPrompt ─── */

    public function test_build_retrieved_products_prompt_with_data(): void
    {
        $method = $this->reflection->getMethod('buildRetrievedProductsPrompt');
        $method->setAccessible(true);

        $analysis = [
            'major_code' => 'CNTT',
            'major_name' => 'Công nghệ thông tin',
            'terms' => ['laravel'],
        ];

        $products = [
            (object) [
                'id' => 1,
                'title' => 'Web bán hàng Laravel',
                'description' => 'Mô tả sản phẩm',
                'major_name' => 'Công nghệ thông tin',
                'major_code' => 'CNTT',
                'category_name' => 'Web App',
                'views' => 10,
                'likes' => 5,
            ],
        ];

        $prompt = $method->invoke($this->chat, 'student', $analysis, $products);

        $this->assertStringContainsString('products', $prompt);
        $this->assertStringContainsString('detail_url', $prompt);
        $this->assertStringContainsString('highlights', $prompt);
        $this->assertStringContainsString('Web bán hàng Laravel', $prompt);
        $this->assertStringNotContainsString('MySQL', $prompt);
    }

    public function test_build_retrieved_products_prompt_empty_products(): void
    {
        $method = $this->reflection->getMethod('buildRetrievedProductsPrompt');
        $method->setAccessible(true);

        $analysis = [
            'major_code' => 'AI',
            'major_name' => 'Trí tuệ nhân tạo',
            'terms' => ['machine learning'],
        ];

        $prompt = $method->invoke($this->chat, 'student', $analysis, []);
        $this->assertStringContainsString('products', $prompt);
    }

    /* ─── containsAny helper test ─── */

    public function test_contains_any_returns_true_when_found(): void
    {
        $reflection = $this->reflection;
        $method = $reflection->getMethod('containsAny');
        $method->setAccessible(true);

        $this->assertTrue($method->invoke($this->chat, 'hello world', ['world']));
        $this->assertTrue($method->invoke($this->chat, 'xin chào', ['chào']));
        $this->assertFalse($method->invoke($this->chat, 'hello', ['xyz']));
        $this->assertFalse($method->invoke($this->chat, '', ['test']));
    }
}
