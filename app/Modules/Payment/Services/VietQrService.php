<?php

namespace App\Modules\Payment\Services;

use App\Shared\Exceptions\BusinessException;

/**
 * Sinh dữ liệu QR chuyển khoản cho đơn hàng.
 *
 * Không gọi API thanh toán, không tự động xác nhận giao dịch và không lưu
 * thông tin ngân hàng vào database. Việc xác nhận tiền thuộc Change 4.
 */
class VietQrService
{
    public function isConfigured(): bool
    {
        return trim((string) config('vietqr.bank_code')) !== ''
            && trim((string) config('vietqr.account_no')) !== ''
            && trim((string) config('vietqr.account_name')) !== '';
    }

    /** @return array<string, string|float> */
    public function buildInstructions(string $orderNumber, float $amount): array
    {
        $bankCode = trim((string) config('vietqr.bank_code'));
        $accountNumber = trim((string) config('vietqr.account_no'));
        $accountName = trim((string) config('vietqr.account_name'));
        $template = trim((string) config('vietqr.template', 'compact2'));

        if (!$this->isConfigured()) {
            throw new BusinessException(
                'VIETQR_NOT_CONFIGURED',
                'Chức năng VietQR chưa được cấu hình thông tin tài khoản nhận tiền.',
                503
            );
        }

        $transferContent = "Thanh toan {$orderNumber}";
        $amountInVnd = (int) round($amount);
        $baseUrl = (string) config('vietqr.image_base_url');
        $path = rawurlencode($bankCode) . '-' . rawurlencode($accountNumber) . '-' . rawurlencode($template) . '.png';
        $query = http_build_query([
            'amount' => $amountInVnd,
            'addInfo' => $transferContent,
            'accountName' => $accountName,
        ], '', '&', PHP_QUERY_RFC3986);

        return [
            'bank_code' => $bankCode,
            'account_number' => $accountNumber,
            'account_name' => $accountName,
            'amount' => $amount,
            'transfer_content' => $transferContent,
            'qr_image_url' => "{$baseUrl}/{$path}?{$query}",
        ];
    }
}
