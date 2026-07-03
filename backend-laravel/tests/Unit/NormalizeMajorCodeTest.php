<?php

namespace Tests\Unit;

use App\Http\Common\NormalizeMajorCode;
use PHPUnit\Framework\TestCase;

class NormalizeMajorCodeTest extends TestCase
{
    private NormalizeMajorCode $normalizer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->normalizer = new NormalizeMajorCode();
    }

    public function test_null_input_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode(null));
    }

    public function test_empty_string_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode(''));
    }

    public function test_whitespace_only_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('   '));
    }

    public function test_unknown_major_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('quantum computing'));
    }

    public function test_numeric_input_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('12345'));
    }

    public function test_special_characters_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('!@#$%^&*()'));
    }

    public function test_case_insensitivity(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('AI'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('CNTT'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('MMT'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('TKDH'));
    }

    public function test_mixed_case_works(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('ArtIfIcIaL InTeLlIgEnCe'));
    }

    public function test_vietnamese_major_names(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('trí tuệ nhân tạo'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('công nghệ thông tin'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('mạng máy tính'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('thiết kế đồ họa'));
    }

    public function test_partial_match_works(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('hoc ve tri tue nhan tao'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('chuyen nganh cntt'));
    }

    public function test_very_long_input_returns_mapped_code(): void
    {
        $long = str_repeat('a', 10000);
        $this->assertNull($this->normalizer->NormalizeMajorCode($long));
    }

    public function test_html_injection_returns_null(): void
    {
        $this->assertNull($this->normalizer->NormalizeMajorCode('<script>alert("test")</script>'));
    }

    public function test_unicode_normalization(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('trí tuệ nhân tạo'));
        $this->assertNull($this->normalizer->NormalizeMajorCode('科技'));
    }

    public function test_english_variants(): void
    {
        $this->assertSame('ai', $this->normalizer->NormalizeMajorCode('artificial intelligence'));
        $this->assertSame('cntt', $this->normalizer->NormalizeMajorCode('information technology'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('computer network'));
        $this->assertSame('mmt', $this->normalizer->NormalizeMajorCode('computer networks'));
        $this->assertSame('tkdh', $this->normalizer->NormalizeMajorCode('graphic design'));
    }
}
