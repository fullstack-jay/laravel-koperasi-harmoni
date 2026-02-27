<?php

declare(strict_types=1);

namespace Modules\V1\Auth\Services;

use Illuminate\Contracts\Auth\Authenticatable;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Modules\V1\Auth\Notifications\Welcome;
use Modules\V1\User\Models\User;
use Modules\V1\User\Resources\UserResource;
use Shared\Helpers\ResponseHelper;

final class AuthenticationService
{

    public static function createToken(User|Authenticatable $user): string
    {
        $expiry = intval(config('sanctum.expiration'));
        $device = Str::limit(request()->userAgent(), 255);

        // Create access token
        $tokenResult = $user->createToken($device, ['*'], now()->addMinutes($expiry));

        // Update with refresh_token
        $accessToken = $tokenResult->accessToken;
        $accessToken->refresh_token = Str::random(64);
        $accessToken->refresh_token_expires_at = now()->addMinutes($expiry * 2); // Refresh token expires later
        $accessToken->save();

        return $tokenResult->plainTextToken;
    }

    public static function authLoginResponse(User|\Modules\V1\Admin\Models\Admin $user, ?string $accessToken = null): \Illuminate\Http\JsonResponse
    {
        $inactivityTimeout = intval(config('sanctum.expiration'));

        $token = $accessToken ?? AuthenticationService::createToken($user);

        // Use appropriate resource based on user type
        if ($user instanceof \Modules\V1\Admin\Models\Admin) {
            $resource = new \Modules\V1\Admin\Resources\AdminResource($user);
        } else {
            $resource = new UserResource($user);
        }

        return ResponseHelper::success(
            data: $resource,
            message: 'Login successful',
            meta: [
                'accessToken' => $token,
                'expiresIn' => now()->addMinutes($inactivityTimeout),
            ]
        );
    }
}
