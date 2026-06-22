<?php

namespace Tests\Feature;

use App\Http\Ai\ContentModeration;
use ReflectionMethod;
use Tests\TestCase;

class ContentModerationDesignTest extends TestCase
{
    public function test_generic_rejection_does_not_block_safe_graphic_design_image(): void
    {
        $result = $this->normalize([
            'approved' => false,
            'reason' => 'Nội dung có hình ảnh không phù hợp với môi trường giáo dục.',
            'violations' => ['Ảnh: nội dung thời trang không phù hợp môi trường giáo dục'],
        ], ['major' => 'tkdh']);

        $this->assertTrue($result['approved']);
        $this->assertSame([], $result['violations']);
    }

    public function test_explicit_adult_violation_still_blocks_graphic_design_image(): void
    {
        $result = $this->normalize([
            'approved' => false,
            'reason' => 'Phát hiện nội dung khỏa thân.',
            'violations' => ['Ảnh: có nội dung khỏa thân rõ ràng'],
        ], ['major' => 'Thiết kế đồ họa']);

        $this->assertFalse($result['approved']);
    }

    private function normalize(array $result, array $payload): array
    {
        $moderation = app(ContentModeration::class);
        $method = new ReflectionMethod($moderation, 'normalizeModerationResult');

        return $method->invoke($moderation, $result, $payload);
    }
}
