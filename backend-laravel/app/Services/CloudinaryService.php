<?php

namespace App\Services;

use Cloudinary\Cloudinary;

class CloudinaryService
{
    protected $cloudinary;

    public function __construct()
    {
        $this->cloudinary = new Cloudinary(config('services.cloudinary.url'));
    }

    public function upload($file)
    {
        $result = $this->cloudinary->uploadApi()->upload(
            $file->getRealPath(),
            [
                'folder' => 'products'
            ]
        );

        return $result['secure_url']; // 🔥 URL ảnh
    }
}
