<?php

use App\Modules\Catalog\Services\CatalogImportService;
use App\Shared\Services\XlsxReader;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\Schedule;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('catalog:inspect-spreadsheets', function (XlsxReader $reader) {
    $basePath = base_path('resources/catalog/import-source/2026-11-04');

    foreach (glob($basePath . '/*.xlsx') ?: [] as $spreadsheet) {
        $rows = $reader->readAssociativeRows($spreadsheet);
        $this->newLine();
        $this->info(basename($spreadsheet) . ': ' . count($rows) . ' dòng dữ liệu');
        $this->table(
            array_keys($rows[0] ?? []),
            array_slice(array_map(static fn (array $row) => array_values($row), $rows), 0, 5)
        );
    }
})->purpose('Kiểm tra dữ liệu Excel nguồn của catalog trước khi import');

Artisan::command('catalog:import {--dry-run : Chỉ kiểm tra, không ghi database hoặc storage}', function (CatalogImportService $importer) {
    $plan = $importer->buildPlan();

    if ($plan['errors'] !== []) {
        $this->error('Import catalog chưa hợp lệ:');
        foreach ($plan['errors'] as $error) {
            $this->line('- ' . $error);
        }
        return self::FAILURE;
    }

    $this->table(
        ['Thao tác', 'Tên sản phẩm', 'Danh mục', 'Ảnh'],
        array_map(static fn (array $operation) => [
            $operation['mode'],
            $operation['database_name'],
            $operation['category_name'] ?: 'Giữ nguyên',
            count($operation['images']),
        ], $plan['operations'])
    );
    $this->info(sprintf(
        'Kế hoạch: tạo %d, cập nhật %d, xử lý %d ảnh.',
        $plan['summary']['create'],
        $plan['summary']['update'],
        $plan['summary']['images']
    ));

    if ($this->option('dry-run')) {
        $this->comment('Dry-run hoàn tất; database và storage chưa bị thay đổi.');
        return self::SUCCESS;
    }

    $summary = $importer->import();
    $this->info(sprintf(
        'Import hoàn tất: tạo %d, cập nhật %d, xử lý %d ảnh.',
        $summary['create'],
        $summary['update'],
        $summary['images']
    ));

    return self::SUCCESS;
})->purpose('Import sản phẩm và gallery ảnh từ manifest catalog nội bộ');
