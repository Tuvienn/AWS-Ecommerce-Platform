<?php

namespace App\Modules\Recommendation\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Recommendation\Requests\ProductRecommendationRequest;
use App\Modules\Recommendation\Services\ProductRecommendationService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;

class ProductRecommendationController extends Controller
{
    public function __construct(
        protected ProductRecommendationService $recommendationService
    ) {}

    public function index(ProductRecommendationRequest $request): JsonResponse
    {
        return ApiResponse::success(
            $this->recommendationService->recommend($request->validated('q'))
        );
    }
}
