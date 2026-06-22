<?php

namespace Database\Seeders;

final class ProductSeedImages
{
    private const POOLS = [
        'AI' => [
            'https://images.pexels.com/photos/8386440/pexels-photo-8386440.jpeg',
            'https://images.pexels.com/photos/8386434/pexels-photo-8386434.jpeg',
            'https://images.pexels.com/photos/8386441/pexels-photo-8386441.jpeg',
            'https://images.pexels.com/photos/8386442/pexels-photo-8386442.jpeg',
            'https://images.pexels.com/photos/8386443/pexels-photo-8386443.jpeg',
            'https://images.pexels.com/photos/8386444/pexels-photo-8386444.jpeg',
            'https://images.pexels.com/photos/8386445/pexels-photo-8386445.jpeg',
            'https://images.pexels.com/photos/8386446/pexels-photo-8386446.jpeg',
            'https://images.pexels.com/photos/8386447/pexels-photo-8386447.jpeg',
            'https://images.pexels.com/photos/8386448/pexels-photo-8386448.jpeg',
            'https://images.pexels.com/photos/6153354/pexels-photo-6153354.jpeg',
            'https://images.pexels.com/photos/6153355/pexels-photo-6153355.jpeg',
        ],
        'CNTT' => [
            'https://images.pexels.com/photos/1181671/pexels-photo-1181671.jpeg',
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg',
            'https://images.pexels.com/photos/270404/pexels-photo-270404.jpeg',
            'https://images.pexels.com/photos/546819/pexels-photo-546819.jpeg',
            'https://images.pexels.com/photos/1181263/pexels-photo-1181263.jpeg',
            'https://images.pexels.com/photos/1181467/pexels-photo-1181467.jpeg',
            'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg',
            'https://images.pexels.com/photos/3861972/pexels-photo-3861972.jpeg',
            'https://images.pexels.com/photos/3861958/pexels-photo-3861958.jpeg',
            'https://images.pexels.com/photos/574071/pexels-photo-574071.jpeg',
            'https://images.pexels.com/photos/1181244/pexels-photo-1181244.jpeg',
            'https://images.pexels.com/photos/1181533/pexels-photo-1181533.jpeg',
        ],
        'MMT' => [
            'https://images.pexels.com/photos/325229/pexels-photo-325229.jpeg',
            'https://images.pexels.com/photos/2588757/pexels-photo-2588757.jpeg',
            'https://images.pexels.com/photos/442150/pexels-photo-442150.jpeg',
            'https://images.pexels.com/photos/1148820/pexels-photo-1148820.jpeg',
            'https://images.pexels.com/photos/2881232/pexels-photo-2881232.jpeg',
            'https://images.pexels.com/photos/2881229/pexels-photo-2881229.jpeg',
            'https://images.pexels.com/photos/2881233/pexels-photo-2881233.jpeg',
            'https://images.pexels.com/photos/5380642/pexels-photo-5380642.jpeg',
            'https://images.pexels.com/photos/5380649/pexels-photo-5380649.jpeg',
            'https://images.pexels.com/photos/60504/security-protection-anti-virus-software-60504.jpeg',
            'https://images.pexels.com/photos/1181675/pexels-photo-1181675.jpeg',
            'https://images.pexels.com/photos/577585/pexels-photo-577585.jpeg',
        ],
        'TKDH' => [
            'https://images.pexels.com/photos/196644/pexels-photo-196644.jpeg',
            'https://images.pexels.com/photos/1779487/pexels-photo-1779487.jpeg',
            'https://images.pexels.com/photos/4348404/pexels-photo-4348404.jpeg',
            'https://images.pexels.com/photos/326503/pexels-photo-326503.jpeg',
            'https://images.pexels.com/photos/265087/pexels-photo-265087.jpeg',
            'https://images.pexels.com/photos/4348401/pexels-photo-4348401.jpeg',
            'https://images.pexels.com/photos/4348403/pexels-photo-4348403.jpeg',
            'https://images.pexels.com/photos/4348405/pexels-photo-4348405.jpeg',
            'https://images.pexels.com/photos/4348406/pexels-photo-4348406.jpeg',
            'https://images.pexels.com/photos/6444/pencil-typography-black-design.jpeg',
            'https://images.pexels.com/photos/1109541/pexels-photo-1109541.jpeg',
            'https://images.pexels.com/photos/3584994/pexels-photo-3584994.jpeg',
        ],
    ];

    public static function for(string $majorCode, int $index, int $count = 6): array
    {
        $pool = self::POOLS[strtoupper($majorCode)] ?? self::POOLS['CNTT'];
        $offset = ($index * 3) % count($pool);
        $images = [];

        for ($i = 0; $i < $count; $i++) {
            $baseUrl = $pool[($offset + $i) % count($pool)];
            $images[] = $baseUrl.'?auto=compress&cs=tinysrgb&w=1200&h=800&fit=crop';
        }

        return $images;
    }
}
