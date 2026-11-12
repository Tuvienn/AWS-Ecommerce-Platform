<?php

namespace App\Modules\Catalog\Services;

use App\Shared\Services\XlsxReader;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use JsonException;
use RuntimeException;

/**
 * Import catalog một lần từ manifest + file XLSX do nhóm quản lý.
 *
 * Lệnh đảm bảo dữ liệu có thể chạy lặp: không tạo trùng product, variant hay
 * product_images. Ảnh chỉ được lấy từ resources/, không tải từ nguồn ngoài.
 */
class CatalogImportService
{
    private const SOURCE_DATE = '2026-11-04';

    /** @var array<string, string> */
    private const LEGACY_PRODUCT_NAMES = [
        'iphone 15 pro max 256gb' => 'iPhone 15 Pro Max',
        'macbook air m3 15 inch' => 'MacBook Air M3',
        'ipad pro m4 11 inch' => 'iPad Pro M4',
        'airpods pro 2 usb c' => 'AirPods Pro 2',
        'dell xps 14 2024' => 'Dell XPS 14 9440',
        'oppo find x7 ultra' => 'OPPO Find X7 Ultra',
        'xiaomi 14 ultra' => 'Xiaomi 14',
        'tai nghe chup tai sony wh 1000xm5' => 'Sony WH-1000XM5',
        'chuot khong day logitech mx master 3s' => 'Logitech MX Master 3S',
        'chuot logitech mx master 3s' => 'Logitech MX Master 3S',
        'jbl charge 5' => 'Loa Bluetooth JBL Charge 5',
        'sony wh 1000xm5' => 'Sony WH-1000XM5',
    ];

    /** @var array<string, string> */
    private const SOURCE_NAME_ALIASES = [
        'airpods pro 2 usb c' => 'airpods 2 usb c',
    ];

    /**
     * Ba sản phẩm này có trong catalog PHP trước đây nhưng chưa có trong seed
     * Laravel. Giá và tồn kho được sao chép từ catalog PHP đã audit, để import
     * vẫn tái lập được sau này mà không cần kết nối database cũ.
     *
     * @var array<string, array{category: string, brand: string, price: int, stock: int}>
     */
    private const LEGACY_PRODUCT_FALLBACKS = [
        'oppo find x7 ultra' => ['category' => 'Điện thoại', 'brand' => 'OPPO', 'price' => 22990000, 'stock' => 30],
        'samsung galaxy watch 6 classic' => ['category' => 'Đồng hồ thông minh', 'brand' => 'Samsung', 'price' => 9990000, 'stock' => 40],
        'sac nhanh samsung 45w' => ['category' => 'Phụ kiện', 'brand' => 'Samsung', 'price' => 990000, 'stock' => 200],
    ];

    /** @var array<string, array{product_name: string, files: array<int, string>}> */
    private const LEGACY_SEED_IMAGES = [
        'asus rog zephyrus g16' => [
            'product_name' => 'ASUS ROG Zephyrus G16',
            'files' => ['1777637936_lap_rog.jpg'],
        ],
    ];

    public function __construct(private readonly XlsxReader $xlsxReader) {}

    /**
     * @return array{operations: array<int, array<string, mixed>>, errors: array<int, string>, summary: array<string, int>}
     */
    public function buildPlan(): array
    {
        $manifest = $this->readManifest();
        $details = $this->groupProductRows($this->xlsxReader->readAssociativeRows($this->detailSpreadsheetPath()));
        $existingDescriptions = $this->groupProductRows($this->xlsxReader->readAssociativeRows($this->existingSpreadsheetPath()));
        $operations = [];
        $errors = [];

        foreach ($manifest['operations'] ?? [] as $position => $operation) {
            $action = (string) ($operation['action'] ?? '');
            $sourceName = trim((string) ($action === 'create'
                ? ($operation['name'] ?? '')
                : ($operation['target_name'] ?? '')));

            if (!in_array($action, ['create', 'update'], true) || $sourceName === '') {
                $errors[] = 'Manifest dòng ' . ($position + 1) . ' không có action hoặc tên sản phẩm hợp lệ.';
                continue;
            }

            $dataName = trim((string) ($operation['name'] ?? $sourceName));
            $dataKey = self::SOURCE_NAME_ALIASES[$this->normaliseName($dataName)] ?? $this->normaliseName($dataName);
            $detailData = $details[$dataKey] ?? null;
            $existingDescriptionData = $existingDescriptions[$dataKey] ?? null;
            $data = $action === 'create'
                ? $detailData
                : ($existingDescriptionData ?? $detailData);
            $fallback = self::LEGACY_PRODUCT_FALLBACKS[$this->normaliseName($sourceName)] ?? null;

            if ($data === null) {
                $errors[] = "Không tìm thấy dữ liệu XLSX cho sản phẩm: {$sourceName}.";
                continue;
            }

            $databaseName = $this->databaseProductName($action, $sourceName, $dataName);
            $existingProduct = DB::table('products')->where('name', $databaseName)->first();

            // Một số dòng manifest được đánh dấu update theo catalog PHP cũ,
            // nhưng Laravel seed chưa có sản phẩm đó. Khi đó phải lấy file chi
            // tiết (có giá/tồn kho) để tạo mới, thay vì file mô tả cũ.
            if ($existingProduct === null && $detailData !== null && $this->hasPrice($detailData)) {
                $data = $detailData;
            }

            $categoryName = $this->firstNonEmpty([
                $operation['category'] ?? null,
                $data['category'] ?? null,
                $fallback['category'] ?? null,
            ]);
            $category = $existingProduct === null
                ? DB::table('categories')->where('name', $categoryName)->first()
                : null;

            if ($existingProduct === null && $category === null) {
                $errors[] = "Không tìm thấy category '{$categoryName}' cho sản phẩm {$sourceName}.";
                continue;
            }

            $slug = $existingProduct->slug ?? $this->uniqueSlug($sourceName);
            $images = $this->prepareImages((array) ($operation['images'] ?? []), $slug, $sourceName, $errors);
            if ($images === []) {
                continue;
            }

            $operations[] = [
                'manifest_action' => $action,
                'mode' => $existingProduct === null ? 'create' : 'update',
                'source_name' => $sourceName,
                'database_name' => $databaseName,
                'product_id' => $existingProduct->id ?? null,
                'slug' => $slug,
                'category_id' => $category->id ?? null,
                'category_name' => $categoryName,
                'brand' => trim((string) ($operation['brand'] ?? $data['brand'] ?? $fallback['brand'] ?? '')),
                'price' => $existingProduct === null
                    ? ($this->hasPrice($data) ? $this->moneyToInt($data['price']) : (int) ($fallback['price'] ?? 0))
                    : 0,
                'stock' => $existingProduct === null
                    ? ($this->hasPrice($data) ? max(0, (int) $data['stock']) : (int) ($fallback['stock'] ?? 0))
                    : 0,
                'description' => $data['description'],
                'specifications' => $data['specifications'],
                'images' => $images,
            ];
        }

        return [
            'operations' => $operations,
            'errors' => $errors,
            'summary' => [
                'create' => count(array_filter($operations, static fn (array $operation) => $operation['mode'] === 'create')),
                'update' => count(array_filter($operations, static fn (array $operation) => $operation['mode'] === 'update')),
                'images' => array_sum(array_map(static fn (array $operation) => count($operation['images']), $operations)),
            ],
        ];
    }

    /** @return array<string, int> */
    public function import(): array
    {
        $plan = $this->buildPlan();
        if ($plan['errors'] !== []) {
            throw new RuntimeException("Import catalog bị dừng:\n- " . implode("\n- ", $plan['errors']));
        }

        foreach ($plan['operations'] as $operation) {
            $createdFiles = $this->copyImages($operation['images']);

            try {
                DB::transaction(function () use ($operation) {
                    $productId = $operation['product_id'];

                    if ($productId === null) {
                        $productId = (int) DB::table('products')->insertGetId([
                            'category_id' => $operation['category_id'],
                            'name' => $operation['database_name'],
                            'slug' => $operation['slug'],
                            'brand' => $operation['brand'] ?: null,
                            'description' => $operation['description'] ?: null,
                            'specifications' => $operation['specifications'] === []
                                ? null
                                : json_encode($operation['specifications'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'status' => 'Active',
                            'created_at' => now('UTC'),
                            'updated_at' => now('UTC'),
                        ]);

                        $sku = $this->importSku($operation['slug']);
                        $variantId = (int) DB::table('product_variants')->insertGetId([
                            'product_id' => $productId,
                            'sku' => $sku,
                            'variant_name' => 'Phiên bản tiêu chuẩn',
                            'price' => $operation['price'],
                            'sale_price' => null,
                            'status' => 'Active',
                            'created_at' => now('UTC'),
                            'updated_at' => now('UTC'),
                        ]);

                        DB::table('inventory')->insert([
                            'variant_id' => $variantId,
                            'quantity' => $operation['stock'],
                            'updated_at' => now('UTC'),
                        ]);
                    } else {
                        DB::table('products')->where('id', $productId)->update([
                            'description' => $operation['description'] ?: DB::raw('description'),
                            'specifications' => $operation['specifications'] === []
                                ? DB::raw('specifications')
                                : json_encode($operation['specifications'], JSON_UNESCAPED_UNICODE | JSON_THROW_ON_ERROR),
                            'updated_at' => now('UTC'),
                        ]);
                    }

                    DB::table('product_images')->where('product_id', $productId)->update(['is_primary' => false]);

                    foreach ($operation['images'] as $index => $image) {
                        $existingImage = DB::table('product_images')
                            ->where('product_id', $productId)
                            ->where('image_path', $image['destination'])
                            ->first();

                        $payload = [
                            'sort_order' => $index,
                            'is_primary' => $index === 0,
                        ];

                        if ($existingImage) {
                            DB::table('product_images')->where('id', $existingImage->id)->update($payload);
                        } else {
                            DB::table('product_images')->insert($payload + [
                                'product_id' => $productId,
                                'image_path' => $image['destination'],
                                'created_at' => now('UTC'),
                            ]);
                        }
                    }
                });
            } catch (\Throwable $exception) {
                foreach ($createdFiles as $path) {
                    Storage::disk($this->catalogDisk())->delete($path);
                }

                throw $exception;
            }
        }

        $this->importLegacySeedImages();

        return $plan['summary'];
    }

    /** @return array<string, mixed> */
    private function readManifest(): array
    {
        try {
            return json_decode((string) file_get_contents($this->manifestPath()), true, 512, JSON_THROW_ON_ERROR);
        } catch (JsonException $exception) {
            throw new RuntimeException('Manifest catalog không phải JSON hợp lệ.', previous: $exception);
        }
    }

    /** @param array<int, array<string, string>> $rows
     * @return array<string, array{brand: string, category: string, price: string, stock: string, description: string, specifications: array<string, string>}>
     */
    private function groupProductRows(array $rows): array
    {
        $products = [];
        $currentKey = null;

        foreach ($rows as $row) {
            $name = trim((string) ($row['Product Name'] ?? ''));
            if ($name !== '') {
                $currentKey = $this->normaliseName($name);
                $products[$currentKey] ??= [
                    'brand' => trim((string) ($row['Brand'] ?? '')),
                    'category' => trim((string) ($row['Category'] ?? '')),
                    'price' => trim((string) ($row['Price'] ?? '')),
                    'stock' => trim((string) ($row['Stock'] ?? '')),
                    'description_lines' => [],
                ];
            }

            if ($currentKey === null) {
                continue;
            }

            $line = trim((string) ($row['Description'] ?? ''));
            if ($line !== '') {
                $products[$currentKey]['description_lines'][] = $line;
            }
        }

        foreach ($products as &$product) {
            $product['description'] = implode("\n", $product['description_lines']);
            $product['specifications'] = $this->toSpecifications($product['description_lines']);
            unset($product['description_lines']);
        }
        unset($product);

        return $products;
    }

    /** @param array<int, string> $lines
     * @return array<string, string>
     */
    private function toSpecifications(array $lines): array
    {
        $specifications = [];
        $fallbackIndex = 1;

        foreach ($lines as $line) {
            $line = trim(ltrim(trim($line), "-• \t"));
            if ($line === '') {
                continue;
            }

            if (str_contains($line, ':')) {
                [$key, $value] = array_map('trim', explode(':', $line, 2));
                if ($key !== '' && $value !== '') {
                    $specifications[$key] = $value;
                    continue;
                }
            }

            $specifications['Thông tin ' . $fallbackIndex++] = $line;
        }

        return $specifications;
    }

    /**
     * @param array<int, string> $relativePaths
     * @param array<int, string> $errors
     * @return array<int, array{source: string, destination: string}>
     */
    private function prepareImages(array $relativePaths, string $slug, string $productName, array &$errors): array
    {
        $maximum = max(1, (int) config('catalog.max_images_per_product', 10));
        if ($relativePaths === [] || count($relativePaths) > $maximum) {
            $errors[] = "Sản phẩm {$productName} phải có từ 1 đến {$maximum} ảnh.";
            return [];
        }

        $root = realpath($this->imageSourceRoot());
        if ($root === false) {
            $errors[] = 'Không tìm thấy thư mục ảnh nguồn của catalog.';
            return [];
        }

        $images = [];
        foreach ($relativePaths as $index => $relativePath) {
            $source = realpath($root . DIRECTORY_SEPARATOR . ltrim($relativePath, '/'));
            if ($source === false || !str_starts_with($source, $root . DIRECTORY_SEPARATOR) || !is_file($source)) {
                $errors[] = "Không tìm thấy ảnh '{$relativePath}' của {$productName}.";
                continue;
            }

            $extension = strtolower(pathinfo($source, PATHINFO_EXTENSION));
            if (!in_array($extension, ['jpg', 'jpeg', 'png', 'webp'], true) || @getimagesize($source) === false) {
                $errors[] = "Ảnh '{$relativePath}' của {$productName} không hợp lệ.";
                continue;
            }

            $fileName = Str::slug(pathinfo($source, PATHINFO_FILENAME));
            $fileName = $fileName !== '' ? $fileName : 'image';
            $images[] = [
                'source' => $source,
                'destination' => "products/catalog/{$slug}/" . sprintf('%02d-%s.%s', $index + 1, $fileName, $extension),
            ];
        }

        return count($images) === count($relativePaths) ? $images : [];
    }

    /** @param array<int, array{source: string, destination: string}> $images
     * @return array<int, string>
     */
    private function copyImages(array $images): array
    {
        $disk = Storage::disk($this->catalogDisk());
        $created = [];

        foreach ($images as $image) {
            if ($disk->exists($image['destination'])) {
                continue;
            }

            $stream = fopen($image['source'], 'rb');
            if ($stream === false || !$disk->put($image['destination'], $stream)) {
                if (is_resource($stream)) {
                    fclose($stream);
                }
                foreach ($created as $path) {
                    $disk->delete($path);
                }
                throw new RuntimeException('Không thể sao chép ảnh catalog vào storage.');
            }
            fclose($stream);
            $created[] = $image['destination'];
        }

        return $created;
    }

    private function databaseProductName(string $action, string $sourceName, string $dataName): string
    {
        if ($action === 'create') {
            return $sourceName;
        }

        $mapped = self::LEGACY_PRODUCT_NAMES[$this->normaliseName($sourceName)] ?? null;
        if ($mapped !== null) {
            return $mapped;
        }

        return $dataName !== $sourceName ? $dataName : $sourceName;
    }

    private function uniqueSlug(string $name): string
    {
        $base = Str::slug($name) ?: 'san-pham';
        $slug = $base;
        $counter = 2;

        while (DB::table('products')->where('slug', $slug)->exists()) {
            $slug = "{$base}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function importSku(string $slug): string
    {
        $base = strtoupper(str_replace('-', '_', $slug));
        $sku = 'IMP-' . substr($base, 0, 80);
        $counter = 2;

        while (DB::table('product_variants')->where('sku', $sku)->exists()) {
            $suffix = '-' . $counter++;
            $sku = 'IMP-' . substr($base, 0, 100 - strlen('IMP-') - strlen($suffix)) . $suffix;
        }

        return $sku;
    }

    private function moneyToInt(string $value): int
    {
        $number = (int) preg_replace('/\D+/', '', $value);
        if ($number <= 0) {
            throw new RuntimeException("Giá sản phẩm không hợp lệ: {$value}");
        }

        return $number;
    }

    /** @param array<string, mixed> $data */
    private function hasPrice(array $data): bool
    {
        return trim((string) ($data['price'] ?? '')) !== '';
    }

    /** @param array<int, mixed> $values */
    private function firstNonEmpty(array $values): string
    {
        foreach ($values as $value) {
            $value = trim((string) $value);
            if ($value !== '') {
                return $value;
            }
        }

        return '';
    }

    private function normaliseName(string $value): string
    {
        $ascii = Str::ascii(mb_strtolower($value));
        return trim((string) preg_replace('/[^a-z0-9]+/', ' ', $ascii));
    }

    private function catalogRoot(): string
    {
        return base_path('resources/catalog/import-source/' . self::SOURCE_DATE);
    }

    private function manifestPath(): string
    {
        return $this->catalogRoot() . '/catalog-image-manifest.json';
    }

    private function detailSpreadsheetPath(): string
    {
        return $this->catalogRoot() . '/chi-tiet-san-pham-full-5-brands.xlsx';
    }

    private function existingSpreadsheetPath(): string
    {
        return $this->catalogRoot() . '/thong-tin-san-pham-da-co-full-5-brands.xlsx';
    }

    private function imageSourceRoot(): string
    {
        return base_path('resources/images/catalog-source/products/incoming');
    }

    private function catalogDisk(): string
    {
        return (string) config('catalog.disk', 'public');
    }

    private function importLegacySeedImages(): void
    {
        $disk = Storage::disk($this->catalogDisk());

        foreach (self::LEGACY_SEED_IMAGES as $imageMapping) {
            $product = DB::table('products')
                ->where('name', $imageMapping['product_name'])
                ->first();

            if ($product === null || DB::table('product_images')->where('product_id', $product->id)->exists()) {
                continue;
            }

            foreach ($imageMapping['files'] as $index => $fileName) {
                $source = base_path('resources/images/catalog-source/products/' . $fileName);
                if (!is_file($source)) {
                    throw new RuntimeException("Không tìm thấy ảnh seed legacy: {$fileName}");
                }

                $destination = "products/catalog/{$product->slug}/" . sprintf('%02d-%s', $index + 1, $fileName);
                if (!$disk->exists($destination)) {
                    $stream = fopen($source, 'rb');
                    if ($stream === false || !$disk->put($destination, $stream)) {
                        if (is_resource($stream)) {
                            fclose($stream);
                        }
                        throw new RuntimeException("Không thể sao chép ảnh seed legacy: {$fileName}");
                    }
                    fclose($stream);
                }

                DB::table('product_images')->updateOrInsert(
                    ['product_id' => $product->id, 'image_path' => $destination],
                    [
                        'sort_order' => $index,
                        'is_primary' => $index === 0,
                        'created_at' => now('UTC'),
                    ]
                );
            }
        }
    }
}
