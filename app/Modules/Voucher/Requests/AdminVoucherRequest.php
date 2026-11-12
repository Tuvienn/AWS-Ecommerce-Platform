<?php

namespace App\Modules\Voucher\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class AdminVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $isCreate = $this->isMethod('post');

        return [
            'code' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:50', 'regex:/^[A-Za-z0-9-]+$/'],
            'name' => [$isCreate ? 'required' : 'sometimes', 'string', 'max:150'],
            'discount_type' => [$isCreate ? 'required' : 'sometimes', 'in:Percentage,FixedAmount'],
            'discount_value' => [$isCreate ? 'required' : 'sometimes', 'numeric', 'gt:0'],
            'max_discount_amount' => ['nullable', 'numeric', 'gt:0'],
            'min_order_amount' => ['nullable', 'numeric', 'min:0'],
            'usage_limit' => ['nullable', 'integer', 'min:1'],
            'per_user_limit' => ['nullable', 'integer', 'min:1'],
            'status' => ['nullable', 'in:Active,Inactive'],
            'starts_at' => ['nullable', 'date'],
            'ends_at' => ['nullable', 'date', 'after_or_equal:starts_at'],
        ];
    }

    protected function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator) {
            $type = $this->input('discount_type');
            $value = (float) $this->input('discount_value', 0);
            if ($type === 'Percentage' && $value > 100) {
                $validator->errors()->add('discount_value', 'Phần trăm giảm không được vượt quá 100%.');
            }
        });
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Dữ liệu voucher không hợp lệ', 422, $validator->errors()->toArray())
        );
    }
}
