<?php

namespace App\Support;

final class SuperAdminImpersonation
{
    public const SESSION_KEY = 'superadmin_impersonator_id';

    public static function isEnabled(): bool
    {
        $raw = config('app.superadmin_impersonation');

        if ($raw !== null && $raw !== '') {
            return filter_var($raw, FILTER_VALIDATE_BOOL);
        }

        return app()->isLocal();
    }

    public static function isImpersonating(): bool
    {
        return session()->has(self::SESSION_KEY);
    }
}
