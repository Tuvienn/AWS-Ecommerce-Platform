<?php

namespace Tests\Feature\Catalog;

use App\Models\User;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Tests\TestCase;

class AdminProductImageDeleteTest extends TestCase
{
    public function test_admin_can_delete_existing_product_image(): void
    {
        config()->set('catalog.disk', 's3');
        Storage::fake('s3');

        $categoryId = DB::table('categories')->insertGetId([
            'name' => 'Danh mục xóa ảnh test',
            'slug' => 'danh-muc-xoa-anh-test',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Sản phẩm xóa ảnh test',
            'slug' => 'san-pham-xoa-anh-test',
            'status' => 'Active',
            'created_at' => now(),
            'updated_at' => now(),
        ]);
        $path = 'products/testing/delete-image.jpg';
        Storage::disk('s3')->put($path, 'test image content');
        $imageId = DB::table('product_images')->insertGetId([
            'product_id' => $productId,
            'image_path' => $path,
            'sort_order' => 0,
            'is_primary' => false,
            'created_at' => now(),
        ]);
        $admin = User::where('email', 'admin@techshop.vn')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson('/api/admin/product-images/' . $imageId)
            ->assertOk()
            ->assertJson([
                'success' => true,
            ]);

        $this->assertDatabaseMissing('product_images', ['id' => $imageId]);
        Storage::disk('s3')->assertMissing($path);
    }

    public function test_admin_receives_404_when_product_image_does_not_exist(): void
    {
        $admin = User::where('email', 'admin@techshop.vn')->firstOrFail();

        $this->actingAs($admin)
            ->deleteJson('/api/admin/product-images/999999')
            ->assertNotFound()
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'RESOURCE_NOT_FOUND',
                ],
            ]);
    }
}
