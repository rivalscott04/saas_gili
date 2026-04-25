<?php

namespace App\Services\TravelAgents;

use App\Models\Booking;
use App\Models\TenantTravelAgentConnection;
use App\Models\TravelAgent;
use Illuminate\Support\Arr;
use Illuminate\Support\Str;

class GetYourGuideBookingSyncService
{
    /**
     * @return array{ok: bool, data?: mixed, error?: array<string, mixed>, reason?: string}
     */
    public function syncCreateBooking(Booking $booking, int $actingTenantId): array
    {
        if ((int) $booking->tenant_id !== $actingTenantId) {
            return ['ok' => false, 'reason' => 'tenant_boundary_violation'];
        }

        $resolution = $this->resolveConnection($actingTenantId);
        if ($resolution['connection'] === null) {
            return ['ok' => false, 'reason' => $resolution['reason'] ?? 'not_connected'];
        }

        /** @var TenantTravelAgentConnection $connection */
        $connection = $resolution['connection'];
        /** @var TravelAgent $travelAgent */
        $travelAgent = $resolution['travel_agent'];

        if ((int) $connection->tenant_id !== (int) $booking->tenant_id) {
            return ['ok' => false, 'reason' => 'connection_tenant_mismatch'];
        }

        $client = new GetYourGuideClient(
            (string) $connection->api_key,
            $connection->api_secret !== null ? (string) $connection->api_secret : null,
            $actingTenantId,
            (int) $travelAgent->id,
        );

        $payload = $this->mapBookingToCreatePayload($booking);
        $idempotencyKey = 'saas-booking-'.$booking->getKey().'-create';
        $result = $client->createBooking($payload, $idempotencyKey);

        $now = now();
        if ($result['ok'] ?? false) {
            $ref = $this->extractExternalBookingRef(is_array($result['data'] ?? null) ? $result['data'] : []);
            $booking->update([
                'external_booking_ref' => $ref ?? $booking->external_booking_ref,
                'external_status' => $this->extractExternalStatus(is_array($result['data'] ?? null) ? $result['data'] : []) ?? $booking->external_status,
                'sync_status' => 'synced',
                'last_synced_at' => $now,
                'last_sync_error' => null,
            ]);

            return $result;
        }

        $message = (string) (Arr::get($result, 'error.message') ?: 'GetYourGuide create failed');
        $booking->update([
            'sync_status' => 'error',
            'last_synced_at' => $now,
            'last_sync_error' => Str::limit($message, 2000),
        ]);

        return $result;
    }

    /**
     * @return array{ok: bool, data?: mixed, error?: array<string, mixed>, reason?: string}
     */
    public function syncCancelBooking(Booking $booking, int $actingTenantId): array
    {
        if ((int) $booking->tenant_id !== $actingTenantId) {
            return ['ok' => false, 'reason' => 'tenant_boundary_violation'];
        }

        $ref = trim((string) ($booking->external_booking_ref ?? ''));
        if ($ref === '') {
            return ['ok' => false, 'reason' => 'missing_external_booking_ref'];
        }

        $resolution = $this->resolveConnection($actingTenantId);
        if ($resolution['connection'] === null) {
            return ['ok' => false, 'reason' => $resolution['reason'] ?? 'not_connected'];
        }

        /** @var TenantTravelAgentConnection $connection */
        $connection = $resolution['connection'];
        /** @var TravelAgent $travelAgent */
        $travelAgent = $resolution['travel_agent'];

        if ((int) $connection->tenant_id !== (int) $booking->tenant_id) {
            return ['ok' => false, 'reason' => 'connection_tenant_mismatch'];
        }

        $client = new GetYourGuideClient(
            (string) $connection->api_key,
            $connection->api_secret !== null ? (string) $connection->api_secret : null,
            $actingTenantId,
            (int) $travelAgent->id,
        );

        $result = $client->cancelBooking($ref, ['reason' => 'supplier_cancel']);

        $now = now();
        if ($result['ok'] ?? false) {
            $booking->update([
                'external_status' => $this->extractExternalStatus(is_array($result['data'] ?? null) ? $result['data'] : []) ?? 'cancelled',
                'sync_status' => 'synced',
                'last_synced_at' => $now,
                'last_sync_error' => null,
            ]);

            return $result;
        }

        $message = (string) (Arr::get($result, 'error.message') ?: 'GetYourGuide cancel failed');
        $booking->update([
            'sync_status' => 'error',
            'last_synced_at' => $now,
            'last_sync_error' => Str::limit($message, 2000),
        ]);

        return $result;
    }

    /**
     * @return array<string, mixed>
     */
    public function mapBookingToCreatePayload(Booking $booking): array
    {
        return [
            'supplier_reference' => (string) ($booking->channel_order_id ?? ''),
            'account_reference' => (string) ($booking->tenant?->code ?? ''),
            'activity_id' => (string) ($booking->external_activity_id ?? ''),
            'option_id' => (string) ($booking->external_option_id ?? ''),
            'datetime' => $booking->tour_start_at?->toIso8601String(),
            'participants' => (int) $booking->participants,
            'customer' => array_filter([
                'name' => $booking->customer_name,
                'email' => $booking->customer_email,
                'phone' => $booking->customer_phone,
            ]),
            'currency' => (string) ($booking->currency ?? ''),
            'net_amount' => $booking->net_amount !== null ? (string) $booking->net_amount : null,
            'internal_booking_id' => (string) $booking->getKey(),
        ];
    }

    /**
     * @return array{connection: TenantTravelAgentConnection|null, travel_agent: TravelAgent|null, reason?: string}
     */
    private function resolveConnection(int $tenantId): array
    {
        $travelAgent = TravelAgent::query()->where('code', 'getyourguide')->first();
        if (! $travelAgent) {
            return ['connection' => null, 'travel_agent' => null, 'reason' => 'travel_agent_missing'];
        }

        $connection = TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenantId)
            ->where('travel_agent_id', $travelAgent->id)
            ->first();

        if (! $connection || $connection->status !== 'connected' || trim((string) $connection->api_key) === '') {
            return ['connection' => null, 'travel_agent' => $travelAgent, 'reason' => 'not_connected'];
        }

        return ['connection' => $connection, 'travel_agent' => $travelAgent];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractExternalBookingRef(array $data): ?string
    {
        foreach (['booking_reference', 'bookingReference', 'id', 'reference'] as $key) {
            if (! empty($data[$key]) && is_scalar($data[$key])) {
                return trim((string) $data[$key]);
            }
        }

        return null;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function extractExternalStatus(array $data): ?string
    {
        foreach (['status', 'booking_status', 'state'] as $key) {
            if (! empty($data[$key]) && is_scalar($data[$key])) {
                return strtolower((string) $data[$key]);
            }
        }

        return null;
    }
}
