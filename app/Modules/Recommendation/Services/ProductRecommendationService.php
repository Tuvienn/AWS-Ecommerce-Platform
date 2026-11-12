<?php

namespace App\Modules\Recommendation\Services;

use App\Modules\Catalog\Services\ProductService;

class ProductRecommendationService
{
    public function __construct(
        protected ProductService $productService
    ) {}

    /**
     * Gợi ý có thể giải thích được, dựa trên catalog hiện có:
     * nhận diện danh mục, thương hiệu, nhu cầu và khoảng ngân sách.
     * Không gọi dịch vụ AI bên thứ ba, nên không cần API key hay gửi dữ liệu tìm kiếm ra ngoài.
     */
    public function recommend(string $query): array
    {
        $intent = $this->analyse($query);
        $catalog = $this->productService->getPublicProducts([
            'page' => 1,
            'page_size' => 48,
            'sort' => 'newest',
        ]);

        $ranked = collect($catalog['items'])
            ->map(function (array $product) use ($intent) {
                [$score, $reasons] = $this->score($product, $intent);
                $product['recommendation_score'] = $score;
                $product['recommendation_reason'] = $reasons
                    ? implode(' · ', array_slice($reasons, 0, 2))
                    : 'Đang có trong catalog TechShop';
                return $product;
            })
            ->filter(fn (array $product) => $product['recommendation_score'] > 0)
            ->sortByDesc('recommendation_score')
            ->take(4)
            ->values()
            ->map(function (array $product) {
                unset($product['recommendation_score']);
                return $product;
            })
            ->all();

        return [
            'query' => trim($query),
            'summary' => $this->summary($intent),
            'signals' => [
                'category' => $intent['categoryLabel'],
                'brand' => $intent['brand'],
                'budget' => $intent['budgetLabel'],
                'need' => $intent['needLabel'],
            ],
            'items' => $ranked,
        ];
    }

    protected function analyse(string $query): array
    {
        $normalized = $this->normalize($query);
        $categoryMap = [
            'dien-thoai' => ['label' => 'điện thoại', 'terms' => ['dien thoai', 'iphone', 'samsung', 'xiaomi', 'oppo', 'smartphone']],
            'laptop' => ['label' => 'laptop', 'terms' => ['laptop', 'may tinh xach tay', 'macbook', 'notebook']],
            'may-tinh-bang' => ['label' => 'máy tính bảng', 'terms' => ['may tinh bang', 'tablet', 'ipad']],
            'phu-kien' => ['label' => 'phụ kiện', 'terms' => ['phu kien', 'chuot', 'ban phim', 'sac']],
            'dong-ho-thong-minh' => ['label' => 'đồng hồ thông minh', 'terms' => ['dong ho', 'watch']],
            'am-thanh' => ['label' => 'âm thanh', 'terms' => ['am thanh', 'tai nghe', 'loa', 'airpods']],
            'man-hinh' => ['label' => 'màn hình', 'terms' => ['man hinh', 'monitor']],
        ];
        $needMap = [
            'gaming' => ['label' => 'chơi game', 'terms' => ['gaming', 'choi game', 'game', 'rtx', 'rog']],
            'camera' => ['label' => 'chụp ảnh / camera', 'terms' => ['camera', 'chup anh', 'quay phim']],
            'study' => ['label' => 'học tập / văn phòng', 'terms' => ['hoc tap', 'sinh vien', 'van phong', 'lam viec']],
            'audio' => ['label' => 'nghe nhạc / giải trí', 'terms' => ['nghe nhac', 'giai tri', 'am thanh']],
        ];
        $brands = ['Apple', 'Samsung', 'Xiaomi', 'OPPO', 'ASUS', 'Dell', 'Sony', 'Logitech', 'Lenovo', 'Acer'];

        $category = null;
        $categoryLabel = null;
        foreach ($categoryMap as $slug => $data) {
            if ($this->containsAny($normalized, $data['terms'])) {
                $category = $slug;
                $categoryLabel = $data['label'];
                break;
            }
        }

        $need = null;
        $needLabel = null;
        foreach ($needMap as $key => $data) {
            if ($this->containsAny($normalized, $data['terms'])) {
                $need = $key;
                $needLabel = $data['label'];
                break;
            }
        }

        $brand = null;
        foreach ($brands as $candidate) {
            if (str_contains($normalized, $this->normalize($candidate))) {
                $brand = $candidate;
                break;
            }
        }

        [$minBudget, $maxBudget, $budgetLabel] = $this->extractBudget($normalized);

        return compact('normalized', 'category', 'categoryLabel', 'need', 'needLabel', 'brand', 'minBudget', 'maxBudget', 'budgetLabel');
    }

    protected function score(array $product, array $intent): array
    {
        $score = 1;
        $reasons = [];
        $haystack = $this->normalize(($product['name'] ?? '') . ' ' . ($product['brand'] ?? ''));
        $category = $product['category']['slug'] ?? null;
        $price = (float) ($product['min_price'] ?? 0);

        if ($intent['category']) {
            if ($category === $intent['category']) {
                $score += 50;
                $reasons[] = 'Phù hợp nhóm ' . $intent['categoryLabel'];
            } else {
                $score -= 20;
            }
        }

        if ($intent['brand']) {
            if (mb_strtolower((string) ($product['brand'] ?? '')) === mb_strtolower($intent['brand'])) {
                $score += 35;
                $reasons[] = 'Đúng thương hiệu ' . $intent['brand'];
            } else {
                $score -= 5;
            }
        }

        if ($intent['maxBudget'] !== null) {
            if ($price <= $intent['maxBudget']) {
                $score += 24;
                $reasons[] = 'Nằm trong ngân sách đã nêu';
            } else {
                $score -= 28;
            }
        }
        if ($intent['minBudget'] !== null && $price >= $intent['minBudget']) {
            $score += 5;
        }

        $needTerms = match ($intent['need']) {
            'gaming' => ['gaming', 'rog', 'rtx', 'legion', 'nitro', 'tuf'],
            'camera' => ['iphone', 'galaxy', 'xiaomi'],
            'study' => ['laptop', 'macbook', 'ipad', 'tablet'],
            'audio' => ['tai nghe', 'airpods', 'loa', 'audio'],
            default => [],
        };
        if ($needTerms && $this->containsAny($haystack, $needTerms)) {
            $score += 18;
            $reasons[] = 'Có dấu hiệu phù hợp nhu cầu ' . $intent['needLabel'];
        } elseif ($intent['need'] === 'gaming' && $category === 'laptop') {
            $score += 8;
        } elseif ($intent['need'] === 'study' && in_array($category, ['laptop', 'may-tinh-bang'], true)) {
            $score += 8;
        } elseif ($intent['need'] === 'audio' && $category === 'am-thanh') {
            $score += 12;
        }

        foreach (preg_split('/\s+/u', $intent['normalized']) as $word) {
            if (mb_strlen($word) >= 3 && str_contains($haystack, $word)) {
                $score += 3;
            }
        }

        if (!empty($product['in_stock'])) {
            $score += 2;
        }
        if (!empty($product['has_sale'])) {
            $score += 1;
        }

        return [$score, $reasons];
    }

    protected function extractBudget(string $query): array
    {
        $toVnd = function (string $number, ?string $unit): float {
            $value = (float) str_replace(',', '.', $number);
            return in_array($unit, ['trieu', 'tr', 'm'], true) ? $value * 1000000 : $value;
        };

        if (preg_match('/(\d+(?:[,.]\d+)?)\s*(trieu|tr|m)?\s*(?:-|den)\s*(\d+(?:[,.]\d+)?)\s*(trieu|tr|m)?/u', $query, $match)) {
            $min = $toVnd($match[1], $match[2] ?: $match[4]);
            $max = $toVnd($match[3], $match[4] ?: $match[2]);
            return [$min, $max, 'từ ' . number_format($min, 0, ',', '.') . ' đến ' . number_format($max, 0, ',', '.') . ' đ'];
        }
        if (preg_match('/(?:duoi|toi da)\s*(\d+(?:[,.]\d+)?)\s*(trieu|tr|m)?/u', $query, $match)) {
            $max = $toVnd($match[1], $match[2] ?? null);
            return [null, $max, 'dưới ' . number_format($max, 0, ',', '.') . ' đ'];
        }
        if (preg_match('/(?:tren|tu)\s*(\d+(?:[,.]\d+)?)\s*(trieu|tr|m)?/u', $query, $match)) {
            $min = $toVnd($match[1], $match[2] ?? null);
            return [$min, null, 'từ ' . number_format($min, 0, ',', '.') . ' đ'];
        }

        return [null, null, null];
    }

    protected function summary(array $intent): string
    {
        $parts = array_filter([
            $intent['categoryLabel'] ? 'nhóm ' . $intent['categoryLabel'] : null,
            $intent['brand'] ? 'thương hiệu ' . $intent['brand'] : null,
            $intent['needLabel'] ? 'nhu cầu ' . $intent['needLabel'] : null,
            $intent['budgetLabel'],
        ]);

        return $parts
            ? 'Tôi ưu tiên sản phẩm theo ' . implode(', ', $parts) . '.'
            : 'Tôi xếp hạng sản phẩm theo mức độ khớp với từ khóa và tình trạng catalog.';
    }

    protected function containsAny(string $haystack, array $terms): bool
    {
        foreach ($terms as $term) {
            if (str_contains($haystack, $this->normalize($term))) {
                return true;
            }
        }
        return false;
    }

    protected function normalize(string $value): string
    {
        $value = mb_strtolower(trim($value));
        return strtr($value, [
            'đ' => 'd', 'á' => 'a', 'à' => 'a', 'ả' => 'a', 'ã' => 'a', 'ạ' => 'a',
            'ă' => 'a', 'ắ' => 'a', 'ằ' => 'a', 'ẳ' => 'a', 'ẵ' => 'a', 'ặ' => 'a',
            'â' => 'a', 'ấ' => 'a', 'ầ' => 'a', 'ẩ' => 'a', 'ẫ' => 'a', 'ậ' => 'a',
            'é' => 'e', 'è' => 'e', 'ẻ' => 'e', 'ẽ' => 'e', 'ẹ' => 'e', 'ê' => 'e',
            'ế' => 'e', 'ề' => 'e', 'ể' => 'e', 'ễ' => 'e', 'ệ' => 'e',
            'í' => 'i', 'ì' => 'i', 'ỉ' => 'i', 'ĩ' => 'i', 'ị' => 'i',
            'ó' => 'o', 'ò' => 'o', 'ỏ' => 'o', 'õ' => 'o', 'ọ' => 'o', 'ô' => 'o',
            'ố' => 'o', 'ồ' => 'o', 'ổ' => 'o', 'ỗ' => 'o', 'ộ' => 'o', 'ơ' => 'o',
            'ớ' => 'o', 'ờ' => 'o', 'ở' => 'o', 'ỡ' => 'o', 'ợ' => 'o',
            'ú' => 'u', 'ù' => 'u', 'ủ' => 'u', 'ũ' => 'u', 'ụ' => 'u', 'ư' => 'u',
            'ứ' => 'u', 'ừ' => 'u', 'ử' => 'u', 'ữ' => 'u', 'ự' => 'u',
            'ý' => 'y', 'ỳ' => 'y', 'ỷ' => 'y', 'ỹ' => 'y', 'ỵ' => 'y',
        ]);
    }
}
