<?php

namespace App\Support;

use App\Models\User;
use Illuminate\Http\Request;

/**
 * Velzon theme demo blades (charts, ecommerce, NFT, etc.) — disabled in production navigation.
 */
final class VelzonDemoPages
{
    public static function enabled(): bool
    {
        return (bool) config('app.velzon_demo_pages', false);
    }

    public static function viewerMayBrowse(?User $user): bool
    {
        return self::enabled() && $user !== null && $user->isSuperAdmin();
    }

    public static function mayRenderView(Request $request): bool
    {
        if (! self::viewerMayBrowse($request->user())) {
            return false;
        }

        $path = trim($request->path(), '/');
        if ($path === '') {
            return false;
        }

        return view()->exists($path);
    }
}
