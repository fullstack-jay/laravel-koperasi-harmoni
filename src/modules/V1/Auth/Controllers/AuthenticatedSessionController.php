<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Controllers;

use App\Http\Controllers\V1\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Modules\V1\Auth\Requests\LoginRequest;
use Modules\V1\Auth\Services\AuthenticationService;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Shared\Helpers\ActivityHelper;
use Shared\Helpers\ResponseHelper;

final class AuthenticatedSessionController extends Controller
{
    /**
     * @OA\Post(
     *     path="/auth/login",
     *     summary="User login",
     *     tags={"Authentication"},
     *     description="Logs in a user and returns an access token",
     *     operationId="loginUser",
     *
     *     @OA\RequestBody(
     *         required=true,
     *         description="User credentials",
     *
     *         @OA\JsonContent(
     *             required={"identity", "password"},
     *
     *             @OA\Property(property="identity", type="string", example="user@example.com or username"),
     *             @OA\Property(property="password", type="string", format="password", example="password123"),
     *         ),
     *     ),
     *
     *     @OA\Response(
     *         response=200,
     *         description="Successful login",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Login successful"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="string", example="200"),
     *             @OA\Property(property="access-token", type="string", example="Bearer {access_token}"),
     *             @OA\Property(property="data", type="object", ref="#/components/schemas/UserResource"),
     *         ),
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     * )
     */
    public function store(LoginRequest $request): \Illuminate\Http\JsonResponse
    {
        $identity = $request->identity;

        // Check if identity is email or username
        $field = filter_var($identity, FILTER_VALIDATE_EMAIL) ? 'email' : 'username';

        // Try to find user in both tables
        $user = User::where($field, $identity)->first();

        // If not found in users, try admins
        if (!$user) {
            $user = \Modules\V1\Admin\Models\Admin::where($field, $identity)->first();
        }

        if (!$user || !Hash::check($request->password, $user->password)) {
            // Track failed login attempts
            $this->trackFailedLogin($request, $user, $field, $identity);

            return ResponseHelper::error('Invalid credentials', 401);
        }

        // Check email verification only for regular users (not admins)
        if ($user instanceof User && !$user->hasVerifiedEmail()) {
            return ResponseHelper::error('Email not verified. Kindly verify your email', 403);
        }

        // Load roles for both admin and user
        if ($user instanceof \Modules\V1\Admin\Models\Admin || $user instanceof User) {
            try {
                // Use query builder to avoid ambiguous column errors
                $roles = \Illuminate\Support\Facades\DB::table('role_user')
                    ->join('roles', 'role_user.role_id', '=', 'roles.id')
                    ->where('role_user.user_id', $user->id)
                    ->select('roles.id', 'roles.name', 'roles.slug')
                    ->get();

                // Set roles as attribute to avoid lazy loading
                $user->setRelation('roles', $roles);
            } catch (\Exception $e) {
                // Roles table doesn't exist or no roles assigned
            }
        }

        // Log login activity
        ActivityHelper::logLogin([
            'ip_address' => $request->ip(),
            'user_agent' => $request->userAgent(),
        ]);

        return AuthenticationService::authLoginResponse($user);
    }

    /**
     * @OA\Post(
     *     path="/auth/logout",
     *     summary="User logout",
     *     description="Logs out the authenticated user and revokes the access token.",
     *     operationId="logout",
     *     tags={"Authentication"},
     *     security={
     *         {"bearerAuth": {}}
     *     },
     *
     *     @OA\Response(
     *         response="204",
     *         description="Logged out successfully",
     *
     *         @OA\JsonContent(
     *
     *             @OA\Property(property="message", type="string", example="Logged out successfully"),
     *             @OA\Property(property="status", type="string", example="success"),
     *             @OA\Property(property="statusCode", type="string", example="204"),
     *         ),
     *     ),
     *
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     * )
     */
    public function destroy(Request $request): \Illuminate\Http\JsonResponse
    {
        if ( ! Auth::check()) {
            return ResponseHelper::error('Unauthenticated', 401);
        }

        // Log logout activity BEFORE revoking token
        ActivityHelper::logLogout([
            'ip_address' => $request->ip(),
        ]);

        $request->user()->tokens()->delete();

        return ResponseHelper::success(message: 'logged out successfully', status: 204);
    }

    /**
     * @OA\Post(
     *     path="/auth/refresh-token",
     *     summary="Refresh the authentication token",
     *     description="Revokes the existing token and generates a new token for the authenticated user.",
     *     tags={"Authentication"},
     *     @OA\Response(
     *         response=200,
     *         description="Token refreshed successfully",
     *         @OA\JsonContent(
     *             @OA\Property(property="status", type="boolean", example="success"),
     *             @OA\Property(property="message", type="string", example="Token refreshed"),
     *             @OA\Property(property="meta", type="object",
     *                 @OA\Property(property="accessToken", type="string", example="1|abc123..."),
     *                 @OA\Property(property="expires_in", type="integer", example=60)
     *             )
     *         )
     *     ),
     *     @OA\Response(response=401, ref="#/components/responses/401"),
     * )
     */
    public function refreshToken(Request $request): \Illuminate\Http\JsonResponse
    {
        $user = auth()->user();
        $user->tokens()->delete(); // Revoke all existing tokens

        $device = Str::limit($request->userAgent(), 255);
        $token = $user->createToken($device)->plainTextToken;

        return ResponseHelper::success(
            message: 'Token refreshed',
            meta: [
                'accessToken' => $token,
                'expires_in' => config('sanctum.expiration'),
            ],
        );
    }

    /**
     * Track failed login attempts
     */
    private function trackFailedLogin(Request $request, $user, string $field, string $identity): void
    {
        // Get or create session key for tracking attempts
        $sessionKey = 'login_attempts_' . $field . '_' . $identity;
        $attempts = session($sessionKey, 0) + 1;
        session([$sessionKey => $attempts]);

        // Get user object for logging (if exists)
        $logUser = $user ?? ($field === 'email'
            ? \Modules\V1\User\Models\User::withTrashed()->where('email', $identity)->first()
            : \Modules\V1\User\Models\User::withTrashed()->where('username', $identity)->first());

        // Try admin if not found in users
        if (!$logUser) {
            $logUser = ($field === 'email'
                ? \Modules\V1\Admin\Models\Admin::withTrashed()->where('email', $identity)->first()
                : \Modules\V1\Admin\Models\Admin::withTrashed()->where('username', $identity)->first());
        }

        $description = $logUser
            ? "Failed login attempt for {$field}: {$identity} (Attempt {$attempts})"
            : "Failed login attempt for non-existent {$field}: {$identity} (Attempt {$attempts})";

        // Log the failed attempt
        \Modules\V1\Logging\Facades\Activity::causedBy($logUser)
            ->event(\Modules\V1\Logging\Enums\LogEventEnum::LOGIN_FAILED)
            ->withProperties([
                'identity_field' => $field,
                'identity_value' => $identity,
                'attempt_number' => $attempts,
                'ip_address' => $request->ip(),
                'user_agent' => $request->userAgent(),
            ])
            ->log($description);

        // If 3rd failed attempt, log additional warning
        if ($attempts >= 3) {
            \Modules\V1\Logging\Facades\Activity::causedBy($logUser)
                ->withProperties([
                    'identity_field' => $field,
                    'identity_value' => $identity,
                    'total_attempts' => $attempts,
                    'ip_address' => $request->ip(),
                    'user_agent' => $request->userAgent(),
                    'blocked' => true,
                ])
                ->log("Multiple failed login attempts ({$attempts}) for {$field}: {$identity}", false, 'multiple_login_failed');

            // Clear attempts after 3 to reset
            session([$sessionKey => 0]);
        }
    }
}
