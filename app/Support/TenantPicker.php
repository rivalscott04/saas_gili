<?php

namespace App\Support;

use App\Models\Tenant;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;

final class TenantPicker
{
    /**
     * @return Collection<int, Tenant>
     */
    public static function optionsForSuperAdmin(): Collection
    {
        $ttl = max(60, (int) config('performance.tenant_picker_cache_seconds', 300));

        return Cache::remember('tenants.picker.options.v1', $ttl, function (): Collection {
            return Tenant::query()
                ->orderBy('name')
                ->get(['id', 'name', 'code']);
        });
    }

    public static function forgetCache(): void
    {
        Cache::forget('tenants.picker.options.v1');
    }
}
