<?php

namespace App\Services;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\GuzzleException;
use Illuminate\Support\Str;
use InvalidArgumentException;

class GetYourGuideSupplierApiPlaygroundService
{
    private const OPERATIONS = [
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

    public static function operationIds(): array
    {
        return array_keys(self::OPERATIONS);
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
        if (! isset(self::OPERATIONS[$operation])) {
            throw new InvalidArgumentException('Unknown operation.');
        }

        [$method, $pathTemplate] = self::OPERATIONS[$operation];
        $path = $this->interpolatePath($pathTemplate, $pathParams);
        $url = $this->buildUrl($baseUrl, $path, $queryParams, $allowLocalHttp);

        $options = [
            'timeout' => max(5, (int) config('gyg_supplier_playground.http_timeout_seconds', 45)),
            'http_errors' => false,
            'headers' => [
                'Accept' => 'application/json',
                'User-Agent' => 'DesmaSupplierApiPlayground/1.0',
            ],
            'auth' => [$authUser, $authPassword],
        ];

        if ($method === 'POST' && $jsonBody !== null && trim($jsonBody) !== '') {
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
        $raw = (string) $response->getBody();
        if (trim($raw) === '') {
            return [
                'ok' => true,
                'status' => $status,
                'duration_ms' => (int) round((microtime(true) - $started) * 1000),
                'body' => null,
                'body_raw' => '',
            ];
        }

        $decoded = json_decode($raw, true);
        $jsonError = json_last_error();

        return [
            'ok' => true,
            'status' => $status,
            'duration_ms' => (int) round((microtime(true) - $started) * 1000),
            'body' => $jsonError === JSON_ERROR_NONE ? $decoded : null,
            'body_raw' => $jsonError === JSON_ERROR_NONE ? null : $raw,
        ];
    }

    private function interpolatePath(string $template, array $pathParams): string
    {
        $path = $template;
        if (preg_match_all('/\{(\w+)\}/', $template, $matches)) {
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
}
