<?php

namespace App\Support;

use App\Models\Tenant;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;

class TenantWebScope
{
    /**
     * Resolve active tenant id for admin pages.
     * Superadmin uses `?tenant=` (tenant code). Tenant admin is locked to their tenant.
     */
    public static function resolveTenantId(Request $request, User $viewer, Collection $availableTenants): int
    {
        if ($request->has('tenant_id')) {
            abort(403);
        }

        if (! $viewer->isSuperAdmin()) {
            if ($request->has('tenant')) {
                abort(403);
            }

            return (int) ($viewer->tenant_id ?? 0);
        }

        $requestedTenantCode = trim((string) $request->query('tenant', ''));
        if ($requestedTenantCode !== '') {
            $tenant = $availableTenants->first(
                fn ($candidate) => strtolower((string) $candidate->code) === strtolower($requestedTenantCode)
            );
            if (! $tenant) {
                abort(404);
            }

            return (int) $tenant->id;
        }

        return (int) optional($availableTenants->first())->id;
    }

    /**
     * Superadmin payload scope: requires `tenant_code` (tenant slug), never numeric tenant ids.
     *
     * @param  array<string, mixed>  $payload
     */
    public static function resolveTenantIdFromPayload(User $viewer, array $payload): int
    {
        if (! $viewer->isSuperAdmin()) {
            if (array_key_exists('tenant_code', $payload) || array_key_exists('tenant_id', $payload)) {
                abort(403);
            }

            return (int) ($viewer->tenant_id ?? 0);
        }

        if (array_key_exists('tenant_id', $payload)) {
            abort(403);
        }

        if (! empty($payload['tenant_code'])) {
            $code = strtolower(trim((string) $payload['tenant_code']));
            $tenantId = (int) Tenant::query()->whereRaw('LOWER(code) = ?', [$code])->value('id');

            return $tenantId;
        }

        return 0;
    }
}
