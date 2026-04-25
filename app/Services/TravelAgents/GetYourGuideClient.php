<?php

namespace App\Services\TravelAgents;

use App\Models\ChannelSyncLog;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\PendingRequest;
use Illuminate\Http\Client\Response;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Str;
use Throwable;

class GetYourGuideClient
{
    private const PATH_AVAILABILITY = 'v1/availability/check';

    private const PATH_BOOKINGS = 'v1/bookings';

    public function __construct(
        private readonly string $apiKey,
        private readonly ?string $apiSecret,
        private readonly int $tenantId,
        private readonly int $travelAgentId,
    ) {
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, data?: mixed, error?: array{http_status?: int, code?: string|null, message: string, retryable?: bool}}
     */
    public function checkAvailability(array $payload): array
    {
        return $this->dispatch(
            'post',
            self::PATH_AVAILABILITY,
            $payload,
            'gyg.availability.checked',
            []
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, data?: mixed, error?: array{http_status?: int, code?: string|null, message: string, retryable?: bool}}
     */
    public function createBooking(array $payload, ?string $idempotencyKey = null): array
    {
        $headers = [];
        if ($idempotencyKey !== null && $idempotencyKey !== '') {
            $headers['Idempotency-Key'] = $idempotencyKey;
        }

        return $this->dispatch(
            'post',
            self::PATH_BOOKINGS,
            $payload,
            'gyg.booking.created',
            $headers
        );
    }

    /**
     * @param  array<string, mixed>  $payload
     * @return array{ok: bool, data?: mixed, error?: array{http_status?: int, code?: string|null, message: string, retryable?: bool}}
     */
    public function cancelBooking(string $externalBookingRef, array $payload = []): array
    {
        $path = self::PATH_BOOKINGS.'/'.rawurlencode($externalBookingRef).'/cancel';

        return $this->dispatch('post', $path, $payload, 'gyg.booking.cancelled', []);
    }

    /**
     * Single lightweight POST to confirm credentials reach GetYourGuide (availability endpoint).
     * Dummy activity/option IDs are expected to yield 4xx if auth is valid; 401/403 means bad credentials.
     *
     * @return array{ok: bool, message: string, context?: array<string, mixed>}
     */
    public function probeConnection(): array
    {
        $path = self::PATH_AVAILABILITY;
        $body = [
            'activity_id' => '__saas_gili_connection_probe__',
            'option_id' => '__saas_gili_probe_option__',
        ];

        try {
            $response = $this->pendingRequest()->post($path, $body);
            $status = $response->status();

            $this->logProbe($path, $status, $response);

            if ($status === 401 || $status === 403) {
                return [
                    'ok' => false,
                    'message' => 'GetYourGuide menolak API key atau secret (HTTP '.$status.').',
                    'context' => ['http_status' => $status],
                ];
            }

            if ($status >= 200 && $status < 300) {
                return [
                    'ok' => true,
                    'message' => 'GetYourGuide merespons sukses — kredensial diterima (HTTP '.$status.').',
                    'context' => ['http_status' => $status],
                ];
            }

            if ($status >= 400 && $status < 500) {
                return [
                    'ok' => true,
                    'message' => 'GetYourGuide merespons — kredensial tampak valid (HTTP '.$status.'; ID aktivitas uji tidak dipakai untuk booking nyata).',
                    'context' => ['http_status' => $status],
                ];
            }

            return [
                'ok' => false,
                'message' => 'GetYourGuide tidak dapat diuji (HTTP '.$status.'). Coba lagi nanti.',
                'context' => ['http_status' => $status],
            ];
        } catch (ConnectionException $e) {
            $this->logProbeFailure($path, $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Tidak terhubung ke GetYourGuide: '.Str::limit($e->getMessage(), 240),
                'context' => ['exception' => $e::class],
            ];
        } catch (Throwable $e) {
            $this->logProbeFailure($path, $e->getMessage());

            return [
                'ok' => false,
                'message' => 'Gagal memanggil GetYourGuide: '.Str::limit($e->getMessage(), 240),
                'context' => ['exception' => $e::class],
            ];
        }
    }

    /**
     * @param  array<string, mixed>  $body
     * @param  array<string, string>  $extraHeaders
     * @return array{ok: bool, data?: mixed, error?: array{http_status?: int, code?: string|null, message: string, retryable?: bool}}
     */
    private function dispatch(string $method, string $path, array $body, string $eventType, array $extraHeaders = []): array
    {
        $maxAttempts = max(1, (int) config('services.getyourguide.retry_max'));

        for ($attempt = 1; $attempt <= $maxAttempts; $attempt++) {
            try {
                $request = $this->pendingRequest();
                if ($extraHeaders !== []) {
                    $request = $request->withHeaders($extraHeaders);
                }
                $response = $request->{$method}($path, $body);
                $status = $response->status();

                if ($response->successful()) {
                    $data = $response->json();
                    $this->logOutbound($eventType, 'success', 'GYG HTTP '.$status, [
                        'path' => $path,
                        'attempts' => $attempt,
                    ]);

                    return ['ok' => true, 'data' => $data];
                }

                if ($this->shouldRetryHttpStatus($status) && $attempt < $maxAttempts) {
                    $this->backoffMicros($attempt);

                    continue;
                }

                $normalized = $this->normalizeHttpFailure($response);
                $this->logOutbound($eventType, 'error', (string) ($normalized['error']['message'] ?? 'GYG error'), [
                    'path' => $path,
                    'http_status' => $status,
                    'attempts' => $attempt,
                ]);

                return $normalized;
            } catch (ConnectionException $e) {
                if ($attempt < $maxAttempts) {
                    $this->backoffMicros($attempt);

                    continue;
                }

                $mapped = $this->normalizedConnectionFailure($e);
                $this->logOutbound($eventType, 'error', $mapped['error']['message'], [
                    'path' => $path,
                    'attempts' => $attempt,
                    'exception' => $e::class,
                ]);

                return $mapped;
            } catch (Throwable $e) {
                $message = Str::limit($e->getMessage(), 500);
                $this->logOutbound($eventType, 'error', $message, [
                    'path' => $path,
                    'attempts' => $attempt,
                    'exception' => $e::class,
                ]);

                return [
                    'ok' => false,
                    'error' => [
                        'message' => $message,
                        'retryable' => false,
                    ],
                ];
            }
        }

        return [
            'ok' => false,
            'error' => [
                'message' => 'GetYourGuide request failed (unexpected).',
                'retryable' => false,
            ],
        ];
    }

    private function pendingRequest(): PendingRequest
    {
        $headers = [
            'Accept' => 'application/json',
            'Authorization' => 'Bearer '.$this->apiKey,
        ];
        if ($this->apiSecret !== null && $this->apiSecret !== '') {
            $headers['X-Api-Secret'] = $this->apiSecret;
        }

        return Http::baseUrl(rtrim((string) config('services.getyourguide.base_url'), '/'))
            ->timeout((int) config('services.getyourguide.timeout_seconds'))
            ->withHeaders($headers)
            ->asJson();
    }

    private function shouldRetryHttpStatus(int $status): bool
    {
        return $status === 429 || $status >= 500;
    }

    private function backoffMicros(int $attempt): void
    {
        usleep(min(1_000_000, 100_000 * (2 ** ($attempt - 1))));
    }

    /**
     * @return array{ok: false, error: array{http_status?: int, code?: string|null, message: string, retryable?: bool}}
     */
    private function normalizeHttpFailure(Response $response): array
    {
        $status = $response->status();
        $json = $response->json();
        $message = 'HTTP '.$status;
        $code = null;
        if (is_array($json)) {
            if (isset($json['message']) && is_string($json['message'])) {
                $message = Str::limit($json['message'], 500);
            }
            if (isset($json['code']) && is_scalar($json['code'])) {
                $code = (string) $json['code'];
            }
        } else {
            $body = $response->body();
            if ($body !== '') {
                $message = Str::limit($body, 500);
            }
        }

        $retryable = $status === 429 || $status >= 500;

        return [
            'ok' => false,
            'error' => [
                'http_status' => $status,
                'code' => $code,
                'message' => $message,
                'retryable' => $retryable,
            ],
        ];
    }

    /**
     * @return array{ok: false, error: array{http_status?: int, code?: string|null, message: string, retryable?: bool}}
     */
    private function normalizedConnectionFailure(ConnectionException $e): array
    {
        return [
            'ok' => false,
            'error' => [
                'message' => Str::limit('Timeout / connection error: '.$e->getMessage(), 500),
                'retryable' => true,
            ],
        ];
    }

    /**
     * @param  array<string, mixed>  $context
     */
    private function logOutbound(string $eventType, string $status, string $message, array $context = []): void
    {
        ChannelSyncLog::query()->create([
            'tenant_id' => $this->tenantId,
            'travel_agent_id' => $this->travelAgentId,
            'event_type' => $eventType,
            'direction' => 'outbound',
            'status' => $status,
            'message' => $message,
            'payload' => null,
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }

    private function logProbe(string $path, int $status, Response $response): void
    {
        $logStatus = ($status === 401 || $status === 403 || $status >= 500) ? 'error' : 'success';
        $snippet = Str::limit((string) $response->body(), 400);

        ChannelSyncLog::query()->create([
            'tenant_id' => $this->tenantId,
            'travel_agent_id' => $this->travelAgentId,
            'event_type' => 'gyg.connection.probe',
            'direction' => 'internal',
            'status' => $logStatus,
            'message' => 'GYG probe HTTP '.$status.' '.$path,
            'payload' => null,
            'context' => [
                'path' => $path,
                'http_status' => $status,
                'body_snippet' => $snippet !== '' ? $snippet : null,
            ],
            'occurred_at' => now(),
        ]);
    }

    private function logProbeFailure(string $path, string $message): void
    {
        ChannelSyncLog::query()->create([
            'tenant_id' => $this->tenantId,
            'travel_agent_id' => $this->travelAgentId,
            'event_type' => 'gyg.connection.probe',
            'direction' => 'internal',
            'status' => 'error',
            'message' => 'GYG probe failed '.$path,
            'payload' => null,
            'context' => [
                'path' => $path,
                'detail' => Str::limit($message, 500),
            ],
            'occurred_at' => now(),
        ]);
    }
}
