<?php

namespace Tests\Feature\Recommendation;

use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\DB;
use Tests\TestCase;

class ProductRecommendationTest extends TestCase
{
    public function test_recommendation_understands_category_need_and_budget(): void
    {
        $now = Carbon::now('UTC')->format('Y-m-d H:i:s.u');
        $categoryId = DB::table('categories')->where('slug', 'laptop')->value('id');

        if (!$categoryId) {
            $categoryId = DB::table('categories')->insertGetId([
                'name' => 'Laptop',
                'slug' => 'laptop',
                'status' => 'Active',
                'created_at' => $now,
                'updated_at' => $now,
            ]);
        }

        $slug = 'ai-gaming-laptop-' . uniqid();
        $productId = DB::table('products')->insertGetId([
            'category_id' => $categoryId,
            'name' => 'Laptop Gaming AI ' . uniqid(),
            'slug' => $slug,
            'brand' => 'ASUS',
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        $variantId = DB::table('product_variants')->insertGetId([
            'product_id' => $productId,
            'sku' => 'AI-GAME-' . strtoupper(uniqid()),
            'variant_name' => 'RTX 4060',
            'price' => 25000000,
            'sale_price' => 22000000,
            'status' => 'Active',
            'created_at' => $now,
            'updated_at' => $now,
        ]);

        DB::table('inventory')->insert([
            'variant_id' => $variantId,
            'quantity' => 5,
            'updated_at' => $now,
        ]);

        $response = $this->getJson('/api/recommendations/products?q=' . urlencode('laptop gaming dưới 30 triệu'));

        $response->assertOk()
            ->assertJsonPath('data.signals.category', 'laptop')
            ->assertJsonPath('data.signals.need', 'chơi game')
            ->assertJsonPath('data.signals.budget', 'dưới 30.000.000 đ')
            ->assertJsonFragment(['slug' => $slug]);
    }
}
