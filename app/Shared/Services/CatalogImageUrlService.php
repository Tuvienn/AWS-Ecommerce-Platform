<?php

namespace App\Shared\Services;

use Illuminate\Support\Facades\Storage;

/**
 * Chuyển đường dẫn ảnh catalog đã lưu trong database thành URL mà trình duyệt dùng được.
 *
 * Ở local, ảnh nằm trên public disk và được phục vụ qua /storage.
 * Ở staging/production, ảnh S3 private phải dùng URL ký tạm thời.
 */
class CatalogImageUrlService
{
    public function format(?string $imagePath): ?string
    {
        if (empty($imagePath)) {
            return null;
        }

        if (filter_var($imagePath, FILTER_VALIDATE_URL)
            || str_starts_with($imagePath, 'http://')
            || str_starts_with($imagePath, 'https://')) {
            return $imagePath;
        }

        $trimmed = ltrim($imagePath, '/');
        if (str_starts_with($trimmed, 'assets/')) {
            return '/' . $trimmed;
        }

        $diskName = (string) config('catalog.disk', 'public');
        if ($diskName === 'public') {
            return '/storage/' . $trimmed;
        }

        $disk = Storage::disk($diskName);
        if ($diskName === 's3') {
            return '/media/catalog/' . $this->encodePath($trimmed);
        }

        return $disk->url($trimmed);
    }

    private function encodePath(string $path): string
    {
        return rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
    }
}
