<?php

namespace App\Modules\Order\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminOrderStatusRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'status' => ['required', 'string', 'in:Pending,Confirmed,Processing,Shipped,Delivered,Cancelled'],
            'reason' => ['nullable', 'string', 'max:500', 'prohibited_unless:status,Cancelled'],
        ];
    }

    public function messages(): array
    {
        return [
            'status.required' => 'Trạng thái đơn hàng là bắt buộc',
            'status.in' => 'Trạng thái không hợp lệ (hợp lệ: Pending, Confirmed, Processing, Shipped, Delivered, Cancelled)',
            'reason.max' => 'Lý do hủy đơn không được vượt quá 500 ký tự',
            'reason.prohibited_unless' => 'Chỉ được gửi lý do khi hủy đơn hàng',
        ];
    }

    protected function failedValidation(Validator $validator)
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
