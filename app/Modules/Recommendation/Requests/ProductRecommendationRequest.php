<?php

namespace App\Modules\Recommendation\Requests;

use App\Shared\Helpers\ApiResponse;
use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;

class ProductRecommendationRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'q' => ['required', 'string', 'min:2', 'max:200'],
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        throw new HttpResponseException(
            ApiResponse::error('VALIDATION_ERROR', 'Câu tìm kiếm cần có từ 2 đến 200 ký tự', 422, $validator->errors()->toArray())
        );
    }
}
