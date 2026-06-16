<?php

namespace App\Http\common;

class NormalizeMajorCode
{
    public function NormalizeMajorCode(?string $value): ?string
    {
        if (!$value) return null;

        $v = mb_strtolower(trim($value), 'UTF-8');

        $map = [
            'ai' => [
                'ai',
                'artificial intelligence',
                'trí tuệ nhân tạo',
            ],
            'cntt' => [
                'cntt',
                'it',
                'công nghệ thông tin',
                'information technology',
            ],
            'mmt' => [
                'mmt',
                'mạng máy tính',
                'computer network',
                'computer networks',
            ],
            'tkdh' => [
                'tkdh',
                'thiết kế đồ họa',
                'graphic design',
            ],
        ];

        foreach ($map as $code => $keywords) {
            foreach ($keywords as $keyword) {
                if ($v === $keyword || str_contains($v, $keyword)) {
                    return $code;
                }
            }
        }

        return null;
    }
}
