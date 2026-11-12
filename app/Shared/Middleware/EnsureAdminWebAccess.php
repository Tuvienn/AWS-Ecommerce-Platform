<?php

namespace App\Shared\Middleware;

use App\Modules\Identity\Repositories\PermissionRepository;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Symfony\Component\HttpFoundation\Response;

class EnsureAdminWebAccess
{
    public function __construct(
        protected PermissionRepository $permissionRepository
    ) {}

    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->guest(route('login'));
        }

        if ($user->status !== 'Active') {
            Auth::guard('web')->logout();

            if ($request->hasSession()) {
                $request->session()->invalidate();
                $request->session()->regenerateToken();
            }

            return redirect()->route('login')->with('error', 'Tài khoản của bạn đã bị khóa.');
        }

        $permissionCodes = $this->permissionRepository->getPermissionCodesByRoleId((int) $user->role_id);

        abort_unless(in_array('admin.dashboard', $permissionCodes, true), 403);

        return $next($request);
    }
}
