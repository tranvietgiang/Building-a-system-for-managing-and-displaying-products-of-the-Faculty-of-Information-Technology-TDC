<?php

namespace App\Services;

use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

class ProductImageDuplicateService
{
    public function check(array $data): ?array
    {
        $majorId = (int) ($data['major_id'] ?? 0);
        $excludedProductId = (int) ($data['replace_product_id'] ?? 0);
        $uploadedFiles = array_values(array_filter(
            $data['images'] ?? [],
            fn($image) => $image instanceof UploadedFile
        ));

        if (!$majorId || empty($uploadedFiles)) {
            return null;
        }

        $uploadedSignatures = $this->uploadedImageSignatures($uploadedFiles);

        if (empty($uploadedSignatures)) {
            return null;
        }

        foreach ($this->candidateImages($majorId, $excludedProductId) as $candidate) {
            $candidateSignature = $this->remoteImageSignature((string) $candidate->image_url);

            if (!$candidateSignature) {
                continue;
            }

            foreach ($uploadedSignatures as $uploaded) {
                $match = $this->compareSignatures($uploaded, $candidateSignature);

                if (!$match) {
                    continue;
                }

                return [
                    'product_id' => (int) $candidate->product_id,
                    'title' => $candidate->title,
                    'status' => $candidate->status,
                    'image_index' => $uploaded['index'],
                    'image_url' => $candidate->image_url,
                    'similarity' => $match['similarity'],
                    'reason' => $match['reason'],
                    'method' => $match['method'],
                ];
            }
        }

        return null;
    }

    private function uploadedImageSignatures(array $uploadedFiles): array
    {
        $signatures = [];

        foreach ($uploadedFiles as $index => $image) {
            $path = $image->getRealPath();

            if (!$path || !is_file($path)) {
                continue;
            }

            $bytes = @file_get_contents($path);

            if ($bytes === false || $bytes === '') {
                continue;
            }

            $signature = $this->signatureFromBytes($bytes);

            if ($signature) {
                $signature['index'] = $index;
                $signature['name'] = $image->getClientOriginalName();
                $signatures[] = $signature;
            }
        }

        return $signatures;
    }

    private function candidateImages(int $majorId, int $excludedProductId)
    {
        $maxProducts = (int) config('product.image_duplicate_max_products', 100);
        $maxImages = (int) config('product.image_duplicate_max_images', 300);

        $products = DB::table('products')
            ->where('major_id', $majorId)
            ->whereIn('status', ['pending', 'approved'])
            ->when($excludedProductId, fn($query) => $query->where('product_id', '!=', $excludedProductId))
            ->latest('product_id')
            ->limit($maxProducts)
            ->get(['product_id', 'title', 'status', 'thumbnail']);

        if ($products->isEmpty()) {
            return collect();
        }

        $productIds = $products->pluck('product_id')->all();
        $productMap = $products->keyBy('product_id');

        $thumbnailImages = $products
            ->filter(fn($product) => !empty($product->thumbnail))
            ->map(fn($product) => (object) [
                'product_id' => $product->product_id,
                'title' => $product->title,
                'status' => $product->status,
                'image_url' => $product->thumbnail,
            ]);

        $galleryImages = DB::table('product_images')
            ->whereIn('product_id', $productIds)
            ->whereNotNull('image_url')
            ->latest('product_image_id')
            ->limit($maxImages)
            ->get(['product_id', 'image_url'])
            ->map(function ($image) use ($productMap) {
                $product = $productMap->get($image->product_id);

                return (object) [
                    'product_id' => $image->product_id,
                    'title' => $product?->title,
                    'status' => $product?->status,
                    'image_url' => $image->image_url,
                ];
            });

        return $thumbnailImages
            ->concat($galleryImages)
            ->filter(fn($image) => !empty($image->image_url))
            ->unique(fn($image) => $this->normalizeImageUrl((string) $image->image_url))
            ->values();
    }

    private function remoteImageSignature(string $url): ?array
    {
        if (!$this->isHttpUrl($url)) {
            return null;
        }

        try {
            $response = Http::connectTimeout(4)
                ->timeout(8)
                ->get($url);

            if (!$response->successful()) {
                return null;
            }

            $bytes = $response->body();

            if ($bytes === '' || strlen($bytes) > (8 * 1024 * 1024)) {
                return null;
            }

            return $this->signatureFromBytes($bytes);
        } catch (\Throwable $exception) {
            Log::warning('Product image duplicate remote fetch failed', [
                'url' => $url,
                'message' => $exception->getMessage(),
            ]);

            return null;
        }
    }

    private function signatureFromBytes(string $bytes): ?array
    {
        $sha256 = hash('sha256', $bytes);
        $averageHash = $this->averageHash($bytes);

        if (!$sha256 && !$averageHash) {
            return null;
        }

        return [
            'sha256' => $sha256,
            'average_hash' => $averageHash,
        ];
    }

    private function compareSignatures(array $uploaded, array $candidate): ?array
    {
        if (
            !empty($uploaded['sha256'])
            && !empty($candidate['sha256'])
            && hash_equals($uploaded['sha256'], $candidate['sha256'])
        ) {
            return [
                'similarity' => 100,
                'reason' => 'Ảnh upload trùng 100% với ảnh của một sản phẩm đã tồn tại.',
                'method' => 'sha256',
            ];
        }

        if (empty($uploaded['average_hash']) || empty($candidate['average_hash'])) {
            return null;
        }

        $distance = $this->hammingDistance($uploaded['average_hash'], $candidate['average_hash']);
        $threshold = (int) config('product.image_duplicate_hamming_threshold', 4);

        if ($distance > $threshold) {
            return null;
        }

        $similarity = max(0, min(100, (int) round(((64 - $distance) / 64) * 100)));

        return [
            'similarity' => $similarity,
            'reason' => 'Ảnh upload gần như trùng với ảnh của một sản phẩm đã tồn tại.',
            'method' => 'average_hash',
        ];
    }

    private function averageHash(string $bytes): ?string
    {
        if (!function_exists('imagecreatefromstring')) {
            return null;
        }

        $source = @imagecreatefromstring($bytes);

        if (!$source) {
            return null;
        }

        $resized = imagecreatetruecolor(8, 8);

        if (!$resized) {
            imagedestroy($source);
            return null;
        }

        imagecopyresampled(
            $resized,
            $source,
            0,
            0,
            0,
            0,
            8,
            8,
            imagesx($source),
            imagesy($source)
        );

        $values = [];

        for ($y = 0; $y < 8; $y++) {
            for ($x = 0; $x < 8; $x++) {
                $rgb = imagecolorat($resized, $x, $y);
                $red = ($rgb >> 16) & 0xFF;
                $green = ($rgb >> 8) & 0xFF;
                $blue = $rgb & 0xFF;

                $values[] = (int) round(($red * 299 + $green * 587 + $blue * 114) / 1000);
            }
        }

        imagedestroy($source);
        imagedestroy($resized);

        if (empty($values)) {
            return null;
        }

        $average = array_sum($values) / count($values);

        return implode('', array_map(
            fn($value) => $value >= $average ? '1' : '0',
            $values
        ));
    }

    private function hammingDistance(string $first, string $second): int
    {
        $length = min(strlen($first), strlen($second));
        $distance = abs(strlen($first) - strlen($second));

        for ($index = 0; $index < $length; $index++) {
            if ($first[$index] !== $second[$index]) {
                $distance++;
            }
        }

        return $distance;
    }

    private function normalizeImageUrl(string $url): string
    {
        $url = trim(mb_strtolower($url, 'UTF-8'));
        $url = explode('?', $url)[0] ?? $url;

        return rtrim($url, '/');
    }

    private function isHttpUrl(string $url): bool
    {
        return (bool) preg_match('/^https?:\/\//i', trim($url));
    }
}
