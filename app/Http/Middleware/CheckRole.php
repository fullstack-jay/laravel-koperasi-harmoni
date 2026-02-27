<?php

declare(strict_types=1);

namespace App\Http\Middleware;

use Closure;
use Illuminate\Http\Request;
use Modules\V1\Admin\Models\Admin;
use Modules\V1\User\Enums\RoleEnum;
use Symfony\Component\HttpFoundation\Response;

class CheckRole
{
    /**
     * Handle an incoming request.
     *
     * @param  \Closure(\Illuminate\Http\Request): (\Symfony\Component\HttpFoundation\Response)  $next
     */
    public function handle(Request $request, Closure $next, string ...$roles): Response
    {
        $user = $request->user();

        if (!$user) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthenticated',
            ], 401);
        }

        // Super Admin memiliki akses ke semua role
        if ($user instanceof Admin) {
            if ($user->super_admin || $user->role_id === RoleEnum::SUPER_ADMIN->value) {
                return $next($request);
            }
        } elseif (isset($user->role_id) && $user->role_id === RoleEnum::SUPER_ADMIN->value) {
            return $next($request);
        }

        // Cek apakah user memiliki method hasRole (Admin model)
        if (method_exists($user, 'hasRole')) {
            foreach ($roles as $role) {
                if ($user->hasRole($role)) {
                    return $next($request);
                }
            }
        }

        return response()->json([
            'success' => false,
            'message' => 'You do not have permission to access this resource',
        ], 403);
    }
}
