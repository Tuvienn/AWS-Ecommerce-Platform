<?php

return [
    /* Disk `public` khi chạy local; đổi thành `s3` ở môi trường AWS. */
    'disk' => env('CATALOG_DISK', 'public'),

    /* Giới hạn thống nhất giữa admin upload và import catalog. */
    'max_images_per_product' => (int) env('CATALOG_MAX_IMAGES_PER_PRODUCT', 10),

    /* Ảnh ở S3 private được cấp URL tạm thời để không phải public bucket. */
    'temporary_url_minutes' => max(1, (int) env('CATALOG_TEMPORARY_URL_MINUTES', 30)),
];
