<?php

namespace App\Support;

use App\Exceptions\PolicyException;

class PolicyResponse
{
    /**
     * @return array<string, mixed>
     */
    public static function payload(PolicyException $exception): array
    {
        return [
            'message' => $exception->getMessage(),
            'error' => [
                'reason_code' => $exception->reasonCode(),
                'status_code' => $exception->statusCode(),
            ],
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function systemAlert(PolicyException $exception): array
    {
        return AccessAlert::fromReason($exception->reasonCode());
    }
}
