<?php

return [
    'duplicate_similarity_threshold' => (int) env('DUPLICATE_SIMILARITY_THRESHOLD', 90),
    'image_duplicate_hamming_threshold' => (int) env('IMAGE_DUPLICATE_HAMMING_THRESHOLD', 4),
    'image_duplicate_max_products' => (int) env('IMAGE_DUPLICATE_MAX_PRODUCTS', 100),
    'image_duplicate_max_images' => (int) env('IMAGE_DUPLICATE_MAX_IMAGES', 300),
];
