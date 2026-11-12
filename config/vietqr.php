<?php

return [
    /* URL ảnh QR của VietQR; chỉ dùng để trình bày dữ liệu chuyển khoản. */
    'image_base_url' => rtrim((string) env('VIETQR_IMAGE_BASE_URL', 'https://img.vietqr.io/image'), '/'),
    'bank_code' => (string) env('VIETQR_BANK_CODE', ''),
    'account_no' => (string) env('VIETQR_ACCOUNT_NO', ''),
    'account_name' => (string) env('VIETQR_ACCOUNT_NAME', ''),
    'template' => (string) env('VIETQR_TEMPLATE', 'compact2'),
];
