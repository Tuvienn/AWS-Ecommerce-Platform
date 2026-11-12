<?php

namespace Tests\Feature\Identity;

use Illuminate\Session\TokenMismatchException;
use Illuminate\Support\Facades\Route;
use Tests\TestCase;

class CsrfExceptionTest extends TestCase
{
    public function test_api_csrf_exception_returns_419_json_response(): void
    {
        Route::get('/api/testing/csrf-token-mismatch', function () {
            throw new TokenMismatchException();
        });

        $this->getJson('/api/testing/csrf-token-mismatch')
            ->assertStatus(419)
            ->assertJson([
                'success' => false,
                'error' => [
                    'code' => 'CSRF_TOKEN_MISMATCH',
                    'message' => 'Phiên bảo mật đã hết hạn. Vui lòng thử lại.',
                ],
            ]);
    }
}
