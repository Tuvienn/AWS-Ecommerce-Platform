<?php

namespace App\Shared\Services;

use RuntimeException;
use SimpleXMLElement;
use ZipArchive;

/**
 * Trình đọc XLSX tối giản, phục vụ riêng cho dữ liệu import nội bộ.
 *
 * Chỉ đọc sheet đầu tiên và trả về mảng các hàng theo tiêu đề cột. Không dùng
 * dependency ngoài để tránh đưa thư viện có cảnh báo bảo mật vào dự án.
 */
class XlsxReader
{
    private const SPREADSHEET_NAMESPACE = 'http://schemas.openxmlformats.org/spreadsheetml/2006/main';

    /**
     * @return array<int, array<string, string>>
     */
    public function readAssociativeRows(string $path): array
    {
        $rows = $this->readRows($path);

        if ($rows === []) {
            return [];
        }

        $headers = array_map(static fn ($value) => trim((string) $value), array_shift($rows));
        $records = [];

        foreach ($rows as $row) {
            $record = [];
            $hasValue = false;

            foreach ($headers as $index => $header) {
                if ($header === '') {
                    continue;
                }

                $value = trim((string) ($row[$index] ?? ''));
                $record[$header] = $value;
                $hasValue = $hasValue || $value !== '';
            }

            if ($hasValue) {
                $records[] = $record;
            }
        }

        return $records;
    }

    /**
     * @return array<int, array<int, string>>
     */
    private function readRows(string $path): array
    {
        if (!is_file($path)) {
            throw new RuntimeException("Không tìm thấy file XLSX: {$path}");
        }

        $archive = new ZipArchive();
        if ($archive->open($path) !== true) {
            throw new RuntimeException("Không thể mở file XLSX: {$path}");
        }

        try {
            $sharedStrings = $this->readSharedStrings($archive);
            $sheetXml = $archive->getFromName('xl/worksheets/sheet1.xml');

            if ($sheetXml === false) {
                throw new RuntimeException('File XLSX không có worksheet đầu tiên.');
            }

            $sheet = $this->loadXml($sheetXml);
            $sheet->registerXPathNamespace('sheet', self::SPREADSHEET_NAMESPACE);
            $result = [];

            foreach ($sheet->xpath('//sheet:sheetData/sheet:row') ?: [] as $row) {
                $cells = [];

                foreach ($row->children(self::SPREADSHEET_NAMESPACE)->c as $cell) {
                    $attributes = $cell->attributes();
                    $column = $this->columnIndex((string) ($attributes['r'] ?? 'A1'));
                    $type = (string) ($attributes['t'] ?? '');
                    $value = '';

                    if ($type === 's') {
                        $sharedIndex = (int) ($cell->children(self::SPREADSHEET_NAMESPACE)->v ?? 0);
                        $value = $sharedStrings[$sharedIndex] ?? '';
                    } elseif ($type === 'inlineStr') {
                        $value = $this->textValue($cell);
                    } else {
                        $value = (string) ($cell->children(self::SPREADSHEET_NAMESPACE)->v ?? '');
                    }

                    $cells[$column] = $value;
                }

                if ($cells !== []) {
                    $lastColumn = max(array_keys($cells));
                    $result[] = array_map(
                        static fn (int $index) => (string) ($cells[$index] ?? ''),
                        range(0, $lastColumn)
                    );
                }
            }

            return $result;
        } finally {
            $archive->close();
        }
    }

    /** @return array<int, string> */
    private function readSharedStrings(ZipArchive $archive): array
    {
        $xml = $archive->getFromName('xl/sharedStrings.xml');
        if ($xml === false) {
            return [];
        }

        $document = $this->loadXml($xml);
        $document->registerXPathNamespace('sheet', self::SPREADSHEET_NAMESPACE);
        $strings = [];

        foreach ($document->xpath('//sheet:si') ?: [] as $item) {
            $strings[] = $this->textValue($item);
        }

        return $strings;
    }

    private function loadXml(string $xml): SimpleXMLElement
    {
        libxml_use_internal_errors(true);
        $document = simplexml_load_string($xml);

        if ($document === false) {
            throw new RuntimeException('Không thể đọc XML bên trong file XLSX.');
        }

        return $document;
    }

    private function textValue(SimpleXMLElement $node): string
    {
        $node->registerXPathNamespace('sheet', self::SPREADSHEET_NAMESPACE);
        $parts = $node->xpath('.//sheet:t') ?: [];

        return implode('', array_map(static fn (SimpleXMLElement $part) => (string) $part, $parts));
    }

    private function columnIndex(string $coordinate): int
    {
        preg_match('/^[A-Z]+/', $coordinate, $match);
        $letters = $match[0] ?? 'A';
        $index = 0;

        foreach (str_split($letters) as $letter) {
            $index = ($index * 26) + (ord($letter) - 64);
        }

        return $index - 1;
    }
}
