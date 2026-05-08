<?php

namespace App\Http\Middleware;

use App\Models\TenantTravelAgentConnection;
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
        $matchedSupplierId = $this->resolveSupplierIdFromDbCredentials($providedUser, $providedPassword);

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
        if ($matchedSupplierId === null) {
            $matchedSupplierId = $this->resolveSupplierIdFromConfigCredentials(
                $providedUser,
                $providedPassword,
                $configuredCredentials
            );
        }

        if ($matchedSupplierId === null) {
            if ($configuredCredentials === []) {
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

        return $next($request);
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
     * @param array<int, mixed> $configuredCredentials
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
