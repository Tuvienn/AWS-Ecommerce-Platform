<?php

namespace Tests\Feature\Catalog;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class CatalogImageProxyTest extends TestCase
{
    public function test_s3_catalog_image_uses_stable_application_url_and_can_be_streamed(): void
    {
        config()->set('catalog.disk', 's3');
        Storage::fake('s3');

        $path = 'products/catalog/test-product/image.jpg';
        Storage::disk('s3')->put($path, 'test image content');

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Danh mục ảnh test',
            'slug' => 'danh-muc-anh-test',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Sản phẩm ảnh test',
            'slug' => 'san-pham-anh-test',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        DB::table('product_images')->insert([
            'product_id' => $productId,
            'image_path' => $path,
            'sort_order' => 0,
            'is_primary' => true,
            'created_at' => now(),
        ]);

        $encoded = rtrim(strtr(base64_encode($path), '+/', '-_'), '=');
        $response = $this->get('/media/catalog/' . $encoded);

        $response->assertOk();
        $this->assertStringContainsString('public', (string) $response->headers->get('Cache-Control'));
        $this->assertStringContainsString('max-age=3600', (string) $response->headers->get('Cache-Control'));
        $this->assertSame('test image content', $response->streamedContent());
    }

    public function test_proxy_rejects_path_not_registered_as_catalog_image(): void
    {
        config()->set('catalog.disk', 's3');
        Storage::fake('s3');

        $path = 'products/private/not-registered.jpg';
        Storage::disk('s3')->put($path, 'private content');
        $encoded = rtrim(strtr(base64_encode($path), '+/', '-_'), '=');

        $this->get('/media/catalog/' . $encoded)->assertNotFound();
    }
}
