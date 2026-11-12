<?php

namespace App\Modules\Favorite\Services;

use App\Shared\Exceptions\BusinessException;
use App\Shared\Services\CatalogImageUrlService;
use Illuminate\Support\Facades\DB;

class FavoriteService
{
    public function __construct(
        protected CatalogImageUrlService $catalogImageUrlService
    ) {}

    public function listForUser(int $userId): array
    {
        $products = DB::table('favorites as favorites')
            ->join('products', 'products.id', '=', 'favorites.product_id')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->leftJoin('product_images as images', function ($join) {
                $join->on('images.product_id', '=', 'products.id')->where('images.is_primary', '=', 1);
            })
            ->where('favorites.user_id', $userId)
            ->where('products.status', 'Active')
            ->where('categories.status', 'Active')
            ->orderByDesc('favorites.created_at')
            ->select(['products.id', 'products.name', 'products.slug', 'products.brand', 'categories.name as category_name', 'categories.slug as category_slug', 'images.image_path'])
            ->get();

        if ($products->isEmpty()) return [];

        $variants = DB::table('product_variants as variants')
            ->leftJoin('inventory', 'inventory.variant_id', '=', 'variants.id')
            ->whereIn('variants.product_id', $products->pluck('id')->all())
            ->where('variants.status', 'Active')
            ->select(['variants.product_id', 'variants.price', 'variants.sale_price', DB::raw('COALESCE(inventory.quantity, 0) as quantity')])
            ->get()
            ->groupBy('product_id');

        return $products->map(function ($product) use ($variants) {
            $minPrice = null;
            $minOriginalPrice = null;
            $hasSale = false;
            $inStock = false;

            foreach ($variants->get($product->id, collect()) as $variant) {
                $price = (float) $variant->price;
                $salePrice = $variant->sale_price !== null ? (float) $variant->sale_price : null;
                $effectivePrice = $salePrice !== null && $salePrice < $price ? $salePrice : $price;
                if ($minPrice === null || $effectivePrice < $minPrice) {
                    $minPrice = $effectivePrice;
                    $minOriginalPrice = $price;
                }
                $hasSale = $hasSale || ($salePrice !== null && $salePrice < $price);
                $inStock = $inStock || ((int) $variant->quantity > 0);
            }

            return [
                'id' => (string) $product->id,
                'name' => $product->name,
                'slug' => $product->slug,
                'brand' => $product->brand,
                'category' => ['name' => $product->category_name, 'slug' => $product->category_slug],
                'primary_image' => $this->catalogImageUrlService->format($product->image_path),
                'min_price' => $minPrice ?? 0.0,
                'min_original_price' => $minOriginalPrice ?? 0.0,
                'has_sale' => $hasSale,
                'in_stock' => $inStock,
            ];
        })->values()->all();
    }

    public function add(int $userId, int $productId): array
    {
        $available = DB::table('products')
            ->join('categories', 'categories.id', '=', 'products.category_id')
            ->where('products.id', $productId)
            ->where('products.status', 'Active')
            ->where('categories.status', 'Active')
            ->exists();

        if (! $available) throw new BusinessException('RESOURCE_NOT_FOUND', 'Sản phẩm không còn khả dụng.', 404);

        DB::table('favorites')->updateOrInsert(
            ['user_id' => $userId, 'product_id' => $productId],
            ['created_at' => now(), 'updated_at' => now()]
        );

        return ['product_id' => (string) $productId, 'is_favorite' => true];
    }

    public function remove(int $userId, int $productId): array
    {
        DB::table('favorites')->where(['user_id' => $userId, 'product_id' => $productId])->delete();
        return ['product_id' => (string) $productId, 'is_favorite' => false];
    }

}
