<?php

namespace App\Modules\Catalog\Controllers;

use Illuminate\Http\Response;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Symfony\Component\HttpFoundation\StreamedResponse;

/**
 * Phục vụ ảnh catalog private thông qua application.
 *
 * Browser chỉ nhận URL thuộc domain của TechShop, còn Laravel kiểm tra ảnh
 * có được đăng ký trong catalog rồi mới đọc file từ storage/S3.
 */
class CatalogImageController
{
    public function show(string $encodedPath): StreamedResponse|Response
    {
        $path = $this->decodePath($encodedPath);

        abort_unless($path !== null && str_starts_with($path, 'products/'), 404);

        $isCatalogImage = DB::table('product_images')->where('image_path', $path)->exists()
            || DB::table('variant_images')->where('image_path', $path)->exists();

        abort_unless($isCatalogImage, 404);

        $disk = Storage::disk((string) config('catalog.disk', 'public'));
        abort_unless($disk->exists($path), 404);

        return $disk->response($path, null, [
            'Cache-Control' => 'public, max-age=3600, stale-while-revalidate=86400',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function decodePath(string $encodedPath): ?string
    {
        if (! preg_match('/^[A-Za-z0-9_-]+$/', $encodedPath)) {
            return null;
        }

        $base64 = strtr($encodedPath, '-_', '+/');
        $base64 .= str_repeat('=', (4 - strlen($base64) % 4) % 4);
        $path = base64_decode($base64, true);

        if (! is_string($path) || $path === '' || str_contains($path, '..') || str_contains($path, "\0")) {
            return null;
        }

        return ltrim($path, '/');
    }
}
