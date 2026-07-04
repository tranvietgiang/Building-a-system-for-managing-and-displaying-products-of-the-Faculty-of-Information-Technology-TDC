<?php

namespace Tests\Feature;

use App\Http\Ai\ChatBoxAi;
use ReflectionClass;
use Tests\TestCase;

class ChatBoxAiIntentTest extends TestCase
{
    public function test_it_detects_vietnamese_feature_intents(): void
    {
        $chat = app(ChatBoxAi::class);
        $method = (new ReflectionClass($chat))->getMethod('detectFeatureIntents');
        $method->setAccessible(true);

        $this->assertSame(['search'], $method->invoke($chat, 'cho tui xem những sản phẩm du lịch'));
        $this->assertSame(['image_check'], $method->invoke($chat, 'kiểm tra hình ảnh sản phẩm như nào'));
        $this->assertSame(['compare'], $method->invoke($chat, 'kiểm tra so sánh đồ trùng lặp'));
        $this->assertSame(['technical_support'], $method->invoke($chat, 'bị lỗi upload ảnh'));
        $this->assertSame(['search'], $method->invoke($chat, 'Hệ thống phát hiện xâm nhập mạng'));
        $this->assertSame(['search'], $method->invoke($chat, 'cho tui xem các sản phẩm về năng suất cây'));
        $this->assertSame(['search'], $method->invoke($chat, 'mạng wifi'));
        $this->assertSame(['search'], $method->invoke($chat, 'cho tui xem sản phẩm kết nối hybrid'));
        $this->assertSame(['search'], $method->invoke($chat, 'có đồ án MMT nào về cloud không'));
        $this->assertSame(['search'], $method->invoke($chat, 'tìm sản phẩm bảo mật mạng'));
        $this->assertSame(['search'], $method->invoke($chat, 'xem sản phẩm mạng máy tính'));
    }

    public function test_it_extracts_vietnamese_search_terms(): void
    {
        $chat = app(ChatBoxAi::class);
        $method = (new ReflectionClass($chat))->getMethod('extractLocalSearchTerms');
        $method->setAccessible(true);

        $this->assertSame(
            ['du lịch', 'du lich', 'lịch', 'lich'],
            $method->invoke($chat, 'cho tui xem những sản phẩm du lịch')
        );
    }

    public function test_it_has_a_large_contextual_reply_bank(): void
    {
        $chat = app(ChatBoxAi::class);
        $method = (new ReflectionClass($chat))->getMethod('nonRelevantReplyBank');
        $method->setAccessible(true);

        $bank = $method->invoke($chat);
        $totalReplies = array_sum(array_map('count', $bank));

        $this->assertGreaterThanOrEqual(100, $totalReplies);
        $this->assertArrayHasKey('greeting', $bank);
        $this->assertArrayHasKey('vague', $bank);
        $this->assertArrayHasKey('off_topic', $bank);
        $this->assertArrayHasKey('study_help', $bank);
        $this->assertArrayHasKey('technical', $bank);
        $this->assertArrayHasKey('capability', $bank);
    }

    public function test_it_analyzes_product_topics_into_majors_and_keywords(): void
    {
        $chat = app(ChatBoxAi::class);
        $method = (new ReflectionClass($chat))->getMethod('analyzeLocalSearchQuery');
        $method->setAccessible(true);

        $network = $method->invoke($chat, 'Hệ thống phát hiện xâm nhập mạng');
        $this->assertSame('MMT', $network['major_code']);
        $this->assertContains('IDS', $network['terms']);
        $this->assertContains('Suricata', $network['terms']);

        $graphic = $method->invoke($chat, 'đồ họa');
        $this->assertSame('GRAPHIC', $graphic['major_code']);
        $this->assertContains('TKDH', $graphic['terms']);

        $automation = $method->invoke($chat, 'tự động hóa');
        $this->assertSame('AI', $automation['major_code']);
        $this->assertContains('automation', $automation['terms']);

        $crop = $method->invoke($chat, 'năng suất cây trồng');
        $this->assertSame('AI', $crop['major_code']);
        $this->assertContains('Ước lượng năng suất cây trồng', $crop['terms']);
        $this->assertContains('nang suat cay trong', $crop['terms']);

        $wifi = $method->invoke($chat, 'mạng wifi');
        $this->assertSame('MMT', $wifi['major_code']);
        $this->assertContains('Mạng Wi-Fi doanh nghiệp quản lý tập trung', $wifi['terms']);
        $this->assertContains('Wi-Fi', $wifi['terms']);
        $this->assertContains('CAPWAP', $wifi['terms']);

        $hybrid = $method->invoke($chat, 'cho tui xem sản phẩm kết nối hybrid');
        $this->assertSame('MMT', $hybrid['major_code']);
        $this->assertContains('Ket noi hybrid cloud an toan', $hybrid['terms']);
        $this->assertContains('Kết nối hybrid cloud an toàn', $hybrid['terms']);
        $this->assertContains('cloud computing', $hybrid['terms']);
        $this->assertContains('network security', $hybrid['terms']);
        $this->assertContains('MMT', $hybrid['terms']);
        $this->assertContains('IPsec', $hybrid['terms']);
        $this->assertContains('BGP', $hybrid['terms']);

        $cloud = $method->invoke($chat, 'có đồ án MMT nào về cloud không');
        $this->assertSame('MMT', $cloud['major_code']);
        $this->assertContains('hybrid cloud', $cloud['terms']);
        $this->assertContains('VPN', $cloud['terms']);

        $generic = $method->invoke($chat, 'giải pháp quản lý');
        $this->assertNull($generic['major_code']);
    }

    public function test_it_builds_prompt_from_retrieved_mysql_products(): void
    {
        $chat = app(ChatBoxAi::class);
        $method = (new ReflectionClass($chat))->getMethod('buildRetrievedProductsPrompt');
        $method->setAccessible(true);

        $prompt = $method->invoke($chat, 'student', [
            'major_code' => 'AI',
            'major_name' => 'Trí tuệ nhân tạo',
            'terms' => ['năng suất cây trồng'],
        ], [
            (object) [
                'id' => 125,
                'title' => 'Ước lượng năng suất cây trồng',
                'description' => 'Dự báo sản lượng từ thời tiết, đất và giống cây.',
                'major_name' => 'Trí tuệ nhân tạo',
                'major_code' => 'AI',
                'category_name' => 'Đồ án',
                'views' => 12,
                'likes' => 3,
                'model_used' => 'Random Forest',
                'ai_framework' => 'Scikit-learn',
                'ai_language' => 'Python',
            ],
        ]);

        $this->assertStringContainsString('products', $prompt);
        $this->assertStringContainsString('detail_url', $prompt);
        $this->assertStringContainsString('highlights', $prompt);
        $this->assertStringContainsString('Random Forest', $prompt);
        $this->assertStringNotContainsString('retrieved_products', $prompt);
        $this->assertStringNotContainsString('MySQL', $prompt);
        $this->assertStringNotContainsString('Backend', $prompt);
        $this->assertStringNotContainsString('"analysis"', $prompt);
        $this->assertStringNotContainsString('model_used', $prompt);
        $this->assertStringNotContainsString('ai_framework', $prompt);
    }

    public function test_no_result_reply_is_short_and_topic_aware(): void
    {
        $chat = app(ChatBoxAi::class);
        $reflection = new ReflectionClass($chat);
        $analyze = $reflection->getMethod('analyzeLocalSearchQuery');
        $reply = $reflection->getMethod('buildNoLocalProductsReply');
        $analyze->setAccessible(true);
        $reply->setAccessible(true);

        $idsReply = $reply->invoke($chat, $analyze->invoke($chat, 'Hệ thống phát hiện xâm nhập mạng nè sao kêu không có'));
        $this->assertStringContainsString('Mình chưa thấy sản phẩm này trong danh sách đã duyệt', $idsReply);
        $this->assertStringContainsString('IDS', $idsReply);
        $this->assertStringContainsString('Suricata', $idsReply);
        $this->assertStringNotContainsString('MySQL', $idsReply);
        $this->assertStringNotContainsString('database', $idsReply);

        $hybridReply = $reply->invoke($chat, $analyze->invoke($chat, 'cho tui xem sản phẩm kết nối hybrid'));
        $this->assertStringContainsString('hybrid cloud', $hybridReply);
        $this->assertStringContainsString('Zero Trust', $hybridReply);
        $this->assertStringNotContainsString('IDS', $hybridReply);
    }
}
