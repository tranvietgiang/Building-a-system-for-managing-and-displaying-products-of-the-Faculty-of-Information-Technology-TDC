<?php

namespace Tests\Unit;

use App\Http\Common\NormalizeMajorCode;
use PHPUnit\Framework\TestCase;

class UploadServiceUnitTest extends TestCase
{
    private NormalizeMajorCode $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new NormalizeMajorCode();
    }

    public function test_normalize_major_code_ai(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('AI'));
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('ai'));
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('artificial intelligence'));
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('trí tuệ nhân tạo'));
    }

    public function test_normalize_major_code_cntt(): void
    {
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('CNTT'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('cntt'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('công nghệ thông tin'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('information technology'));
    }

    public function test_normalize_major_code_mmt(): void
    {
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('MMT'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('mạng máy tính'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('computer network'));
    }

    public function test_normalize_major_code_tkdh(): void
    {
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('TKDH'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('thiết kế đồ họa'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('graphic design'));
    }

    public function test_normalize_major_code_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode(null));
    }

    public function test_normalize_major_code_empty(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode(''));
    }

    public function test_normalize_major_code_unknown(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('unknown_major_xyz'));
    }

    public function test_normalize_major_code_numeric(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('12345'));
    }

    public function test_normalize_major_code_special_chars(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('!@#$%'));
    }

    public function test_normalize_major_code_html_injection(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('<script>alert("xss")</script>'));
    }

    public function test_normalize_major_code_whitespace_only(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('   '));
    }

    public function test_normalize_major_code_case_insensitive(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('Ai'));
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('AI'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('Cntt'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('Mmt'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('Tkdh'));
    }

    public function test_normalize_major_code_edge_toolong(): void
    {
        $long = str_repeat('CNTT', 50);
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode($long));
    }

    public function test_normalize_major_code_php_injection(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('<?php echo "hello"; ?>'));
    }

    public function test_normalize_major_code_unicode_normalization(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('trÍ tuỆ nhÂn tẠO'));
    }

    public function test_normalize_major_code_english_variants(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('artificial intelligence'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('it'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('computer networks'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('graphic design'));
    }
}
