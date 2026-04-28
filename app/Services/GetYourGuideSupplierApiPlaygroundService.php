<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GetYourGuideSupplierApiPlaygroundService
{
    /** Operations against your supplier base URL (GYG → you). */
    private const INBOUND_OPERATIONS = [
        'get_availabilities' => ['GET', '/1/get-availabilities/'],
        'post_reserve' => ['POST', '/1/reserve/'],
        'post_cancel_reservation' => ['POST', '/1/cancel-reservation/'],
        'post_book' => ['POST', '/1/book/'],
        'post_cancel_booking' => ['POST', '/1/cancel-booking/'],
        'post_notify' => ['POST', '/1/notify/'],
        'get_pricing_categories' => ['GET', '/1/products/{productId}/pricing-categories/'],
        'get_supplier_products' => ['GET', '/1/suppliers/{supplierId}/products/'],
        'get_addons' => ['GET', '/1/products/{productId}/addons/'],
        'get_product_details' => ['GET', '/1/products/{productId}'],
    ];

    /**
     * Operations against GetYourGuide {@see supplier-api-gyg-endpoints.yaml} (you → GYG).
     * Base URL host must be allow-listed in config gyg_supplier_playground.allowed_gyg_supplier_api_hosts.
     */
    private const HOST_OPERATIONS = [
        'gyg_host_get_deals' => ['GET', '/1/deals'],
        'gyg_host_post_deals' => ['POST', '/1/deals'],
        'gyg_host_delete_deal' => ['DELETE', '/1/deals/{dealId}'],
        'gyg_host_patch_product_activate' => ['PATCH', '/1/products/{gygOptionId}/activate'],
        'gyg_host_post_redeem_ticket' => ['POST', '/1/redeem-ticket'],
        'gyg_host_post_redeem_booking' => ['POST', '/1/redeem-booking'],
        'gyg_host_post_notify_availability_update' => ['POST', '/1/notify-availability-update'],
        'gyg_host_post_suppliers' => ['POST', '/1/suppliers'],
    ];

    public static function operationIds(): array
    {
        return array_keys(array_merge(self::INBOUND_OPERATIONS, self::HOST_OPERATIONS));
    }

    /**
     * @param  array<string, string>  $pathParams  e.g. ['productId' => 'prod123']
     * @param  array<string, string>  $queryParams
     */
    public function invoke(
        string $operation,
        string $baseUrl,
        string $authUser,
        string $authPassword,
        array $pathParams,
        array $queryParams,
        ?string $jsonBody,
        bool $allowLocalHttp,
    ): array {
        [$method, $pathTemplate, $isHostOperation] = $this->resolveOperation($operation);
        if ($isHostOperation) {
            $this->assertAllowedGygSupplierApiHost($baseUrl);
        }

        $path = $this->interpolatePath($pathTemplate, $pathParams);
        $url = $this->buildUrl($baseUrl, $path, $queryParams, $allowLocalHttp);

        $options = [
            'timeout' => max(5, (int) config('gyg_supplier_playground.http_timeout_seconds', 45)),
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => $isHostOperation
                    ? 'DesmaGygSupplierHostApiPlayground/1.0'
                    : 'DesmaSupplierApiPlayground/1.0',
            ],
            'auth' => [$authUser, $authPassword],
        ];

        if (in_array($method, ['POST', 'PATCH'], true) && $jsonBody !== null && trim($jsonBody) !== '') {
            $options['headers']['Content-Type'] = 'application/json';
            $options['body'] = $jsonBody;
        }

        $started = microtime(true);

        try {
            $response = (new Client)->request($method, $url, $options);
        } catch (GuzzleException $e) {
            return [
                'ok' => false,
                'error' => Str::limit($e->getMessage(), 2000),
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            ];
        }

        $status = $response->getStatusCode();
        $httpOk = $status >= 200 && $status < 300;
        $raw = (string) $response->getBody();
        if (trim($raw) === '') {
            return [
                'ok' => $httpOk,
                'status' => $status,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'body' => null,
                'body_raw' => '',
            ];
        }

        $decoded = json_decode($raw, true);
        $jsonError = json_last_error();

        return [
            'ok' => $httpOk,
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'body' => $jsonError === JSON_ERROR_NONE ? $decoded : null,
            'body_raw' => $jsonError === JSON_ERROR_NONE ? null : $raw,
        ];
    }

    private function interpolatePath(string $template, array $pathParams): string
    {
        $path = $template;
        if (preg_match_all('/\{([^}]+)\}/', $template, $matches)) {
            foreach ($matches[1] as $name) {
                $value = $pathParams[$name] ?? '';
                if ($value === '') {
                    throw new InvalidArgumentException("Missing path parameter: {$name}");
                }
                $path = str_replace('{'.$name.'}', rawurlencode((string) $value), $path);
            }
        }

        return $path;
    }

    /**
     * @param  array<string, string>  $queryParams
     */
    private function buildUrl(string $baseUrl, string $path, array $queryParams, bool $allowLocalHttp): string
    {
        $baseUrl = trim($baseUrl);
        $parts = parse_url($baseUrl);
        if ($parts === false || ! isset($parts['scheme'], $parts['host'])) {
            throw new InvalidArgumentException('Invalid base URL.');
        }

        $scheme = strtolower((string) $parts['scheme']);
        if ($scheme === 'https') {
            // ok
        } elseif ($scheme === 'http' && $allowLocalHttp) {
            $host = strtolower((string) $parts['host']);
            if ($host !== 'localhost' && $host !== '127.0.0.1' && ! Str::endsWith($host, '.test')) {
                throw new InvalidArgumentException('HTTP is only allowed for localhost, 127.0.0.1, or *.test when APP_ENV=local.');
            }
        } else {
            throw new InvalidArgumentException('Only https URLs are allowed (http allowed on local dev hosts only).');
        }

        $path = str_starts_with($path, '/') ? $path : '/'.$path;
        $basePath = isset($parts['path']) ? rtrim((string) $parts['path'], '/') : '';
        $fullPath = ($basePath === '' ? '' : $basePath).$path;

        $query = http_build_query($queryParams, '', '&', PHP_QUERY_RFC3986);
        $port = isset($parts['port']) ? ':'.$parts['port'] : '';

        return $scheme.'://'.$parts['host'].$port.$fullPath.($query !== '' ? '?'.$query : '');
    }

    /**
     * @return array{0: string, 1: string, 2: bool} method, pathTemplate, isHostOperation
     */
    private function resolveOperation(string $operation): array
    {
        if (isset(self::HOST_OPERATIONS[$operation])) {
            [$method, $path] = self::HOST_OPERATIONS[$operation];

            return [$method, $path, true];
        }
        if (isset(self::INBOUND_OPERATIONS[$operation])) {
            [$method, $path] = self::INBOUND_OPERATIONS[$operation];

            return [$method, $path, false];
        }

        throw new InvalidArgumentException('Unknown operation.');
    }

    private function assertAllowedGygSupplierApiHost(string $baseUrl): void
    {
        $parts = parse_url(trim($baseUrl));
        if ($parts === false || ! isset($parts['host'])) {
            throw new InvalidArgumentException('Invalid base URL for GetYourGuide host API.');
        }

        $host = strtolower((string) $parts['host']);
        $allowed = config('gyg_supplier_playground.allowed_gyg_supplier_api_hosts', []);
        $allowed = is_array($allowed) ? $allowed : [];

        if ($allowed === []) {
            $allowed = ['supplier-api.getyourguide.com'];
        }

        if (! in_array($host, $allowed, true)) {
            throw new InvalidArgumentException(
                'This operation may only call an allow-listed GetYourGuide supplier API host. '.
                'Set base URL to https://supplier-api.getyourguide.com (or add the host to GYG_SUPPLIER_PLAYGROUND_ALLOWED_HOSTS).'
            );
        }
    }
}
