<?php

namespace App\Http\Middleware;

use App\Models\Tenant;
use App\Models\TenantTravelAgentConnection;
use App\Models\Tour;
use App\Models\TravelAgent;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureGygSupplierBasicAuth
{
    public function handle(Request $request, Closure $next): Response
    {
        $providedUser = trim((string) $request->getUser());
        $providedPassword = trim((string) $request->getPassword());

        $configuredCredentials = (array) config('gyg_supplier_api.credentials', []);
        $fallbackUsername = trim((string) config('gyg_supplier_api.username', ''));
        $fallbackPassword = trim((string) config('gyg_supplier_api.password', ''));
        $fallbackSupplierId = trim((string) config('gyg_supplier_api.supplier_id', ''));
        if ($fallbackUsername !== '' && $fallbackPassword !== '' && $fallbackSupplierId !== '') {
            $configuredCredentials[] = [
                'username' => $fallbackUsername,
                'password' => $fallbackPassword,
                'supplier_id' => $fallbackSupplierId,
            ];
        }

        $matchedSupplierId = $this->resolveSupplierIdFromDbCredentials($providedUser, $providedPassword);
        if ($matchedSupplierId === null) {
            $matchedSupplierId = $this->resolveSupplierIdFromConfigCredentials(
                $providedUser,
                $providedPassword,
                $configuredCredentials
            );
        }

        $isPlatform = false;
        if ($matchedSupplierId === null) {
            $platformUser = trim((string) config('gyg_supplier_api.platform_username', ''));
            $platformPass = trim((string) config('gyg_supplier_api.platform_password', ''));
            if ($platformUser !== '' && $platformPass !== ''
                && hash_equals($platformUser, $providedUser)
                && hash_equals($platformPass, $providedPassword)) {
                $matchedSupplierId = $this->resolveSupplierIdForPlatformRequest($request) ?? '';
                $isPlatform = true;
            }
        }

        if ($matchedSupplierId === null) {
            if (! $this->hasAnySupplierAuthConfigured($configuredCredentials)) {
                return response()->json([
                    'errorCode' => 'AUTHORIZATION_FAILURE',
                    'errorMessage' => 'Supplier API credentials are not configured',
                ], 503);
            }

            return response()->json([
                'errorCode' => 'AUTHORIZATION_FAILURE',
                'errorMessage' => 'Invalid credentials',
            ], 401, [
                'WWW-Authenticate' => 'Basic realm="GetYourGuide Supplier API"',
            ]);
        }

        $request->attributes->set('gyg_supplier_id', $matchedSupplierId);
        $request->attributes->set('gyg_supplier_platform_auth', $isPlatform);

        return $next($request);
    }

    private function platformCredentialsConfigured(): bool
    {
        $platformUser = trim((string) config('gyg_supplier_api.platform_username', ''));
        $platformPass = trim((string) config('gyg_supplier_api.platform_password', ''));

        return $platformUser !== '' && $platformPass !== '';
    }

    /**
     * @param  array<int, array{username: string, password: string, supplier_id: string}>  $configuredCredentials
     */
    private function hasAnySupplierAuthConfigured(array $configuredCredentials): bool
    {
        if ($configuredCredentials !== []) {
            return true;
        }
        if ($this->platformCredentialsConfigured()) {
            return true;
        }

        $travelAgentId = TravelAgent::query()
            ->whereRaw('LOWER(code) = ?', ['getyourguide'])
            ->value('id');
        if (! $travelAgentId) {
            return false;
        }

        $connections = TenantTravelAgentConnection::query()
            ->where('travel_agent_id', (int) $travelAgentId)
            ->where('status', 'connected')
            ->get(['extra_config']);
        foreach ($connections as $connection) {
            $extra = is_array($connection->extra_config) ? $connection->extra_config : [];
            $user = trim((string) ($extra['supplier_basic_username'] ?? ''));
            $pass = trim((string) ($extra['supplier_basic_password'] ?? ''));
            if ($user !== '' && $pass !== '') {
                return true;
            }
        }

        return false;
    }

    private function resolveSupplierIdForPlatformRequest(Request $request): ?string
    {
        $route = $request->route();
        if ($route !== null && $route->hasParameter('supplierId')) {
            $supplierId = trim((string) $route->parameter('supplierId'));
            if ($supplierId !== '' && $this->tenantExistsForSupplierCode($supplierId)) {
                return $supplierId;
            }

            return null;
        }

        $productId = null;
        if ($route !== null && $route->hasParameter('productId')) {
            $productId = trim((string) $route->parameter('productId'));
        }
        if ($productId === null || $productId === '') {
            $productId = trim((string) $request->query('productId', ''));
        }
        if ($productId === '') {
            $productId = trim((string) $request->input('data.productId', ''));
        }

        if ($productId !== '') {
            return $this->resolveSupplierCodeFromProductId($productId);
        }

        return null;
    }

    private function tenantExistsForSupplierCode(string $supplierId): bool
    {
        return Tenant::query()
            ->whereRaw('LOWER(code) = ?', [strtolower($supplierId)])
            ->exists();
    }

    private function resolveSupplierCodeFromProductId(string $productId): ?string
    {
        $productId = trim($productId);
        if ($productId === '') {
            return null;
        }

        $tours = Tour::query()
            ->with('tenant')
            ->where('is_active', true)
            ->where(function ($query) use ($productId): void {
                $query->where('code', $productId);
                if (ctype_digit($productId)) {
                    $query->orWhere('id', (int) $productId);
                }
            })
            ->get();

        if ($tours->count() !== 1) {
            return null;
        }

        $code = trim((string) ($tours->first()->tenant?->code ?? ''));

        return $code !== '' ? $code : null;
    }

    private function resolveSupplierIdFromDbCredentials(string $providedUser, string $providedPassword): ?string
    {
        if ($providedUser === '' || $providedPassword === '') {
            return null;
        }

        $travelAgentId = TravelAgent::query()
            ->whereRaw('LOWER(code) = ?', ['getyourguide'])
            ->value('id');
        if (! $travelAgentId) {
            return null;
        }

        $connections = TenantTravelAgentConnection::query()
            ->with('tenant:id,code')
            ->where('travel_agent_id', (int) $travelAgentId)
            ->where('status', 'connected')
            ->get();

        foreach ($connections as $connection) {
            $extra = is_array($connection->extra_config) ? $connection->extra_config : [];
            $candidateUser = trim((string) ($extra['supplier_basic_username'] ?? ''));
            $candidatePassword = trim((string) ($extra['supplier_basic_password'] ?? ''));
            $candidateSupplierId = trim((string) ($extra['supplier_id'] ?? ''));
            if ($candidateSupplierId === '') {
                $candidateSupplierId = trim((string) ($connection->tenant?->code ?? $connection->account_reference ?? ''));
            }

            if ($candidateUser === '' || $candidatePassword === '' || $candidateSupplierId === '') {
                continue;
            }

            if (hash_equals($candidateUser, $providedUser) && hash_equals($candidatePassword, $providedPassword)) {
                return $candidateSupplierId;
            }
        }

        return null;
    }

    /**
     * @param  array<int, mixed>  $configuredCredentials
     */
    private function resolveSupplierIdFromConfigCredentials(
        string $providedUser,
        string $providedPassword,
        array $configuredCredentials
    ): ?string {
        foreach ($configuredCredentials as $credential) {
            if (! is_array($credential)) {
                continue;
            }
            $candidateUser = trim((string) ($credential['username'] ?? ''));
            $candidatePassword = trim((string) ($credential['password'] ?? ''));
            $candidateSupplierId = trim((string) ($credential['supplier_id'] ?? ''));
            if ($candidateUser === '' || $candidatePassword === '' || $candidateSupplierId === '') {
                continue;
            }

            if (hash_equals($candidateUser, $providedUser) && hash_equals($candidatePassword, $providedPassword)) {
                return $candidateSupplierId;
            }
        }

        return null;
    }
}
