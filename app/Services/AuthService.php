<?php

namespace App\Services;

use App\Exceptions\PolicyException;
use App\Models\User;
use App\Support\AccessAlert;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;
use Laravel\Sanctum\PersonalAccessToken;

class AuthService
{
    /**
     * @return array{user: User, plain_text_token: string}
     */
    public function login(string $email, string $password): array
    {
        $user = User::query()->where('email', $email)->first();

        if ($user === null || ! Hash::check($password, $user->password)) {
            throw ValidationException::withMessages([
                'email' => [trans('auth.failed')],
            ]);
        }

        if ($user->isSuspended()) {
            throw PolicyException::forbidden(
                AccessAlert::REASON_USER_SUSPENDED,
                'User access is suspended.',
            );
        }

        if ($user->hasExpiredSubscription()) {
            throw PolicyException::forbidden(
                AccessAlert::REASON_SUBSCRIPTION_EXPIRED,
                'Subscription is no longer active.',
            );
        }

        Log::info('auth.login', [
            'user_id' => $user->id,
        ]);

        return [
            'user' => $user,
            'plain_text_token' => $user->createToken('spa', ['*'], now()->addDays(30))->plainTextToken,
        ];
    }

    public function logout(?string $bearerToken): void
    {
        if ($bearerToken === null || $bearerToken === '') {
            return;
        }

        $token = PersonalAccessToken::findToken($bearerToken);
        $userId = $token?->tokenable_id;
        $token?->delete();

        Log::info('auth.logout', [
            'user_id' => $userId,
        ]);
    }
}
