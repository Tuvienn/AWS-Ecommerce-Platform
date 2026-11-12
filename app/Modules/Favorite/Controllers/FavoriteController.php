<?php

namespace App\Modules\Favorite\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Favorite\Services\FavoriteService;
use App\Shared\Helpers\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class FavoriteController extends Controller
{
    public function __construct(protected FavoriteService $favoriteService) {}

    public function index(Request $request): JsonResponse
    {
        return ApiResponse::success($this->favoriteService->listForUser((int) $request->user()->id));
    }

    public function store(Request $request, int $productId): JsonResponse
    {
        return ApiResponse::success($this->favoriteService->add((int) $request->user()->id, $productId), 201);
    }

    public function destroy(Request $request, int $productId): JsonResponse
    {
        return ApiResponse::success($this->favoriteService->remove((int) $request->user()->id, $productId));
    }
}
