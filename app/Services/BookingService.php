<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Models\BookingStatusEvent;
use App\Models\Customer;
use App\Models\Tour;
use App\Models\User;
use Carbon\CarbonImmutable;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;
use Illuminate\Support\Str;

class BookingService
{
    public function __construct(
        private readonly TourCapacityService $tourCapacityService,
        private readonly TenantAuditLogService $tenantAuditLogService,
        private readonly TourAllocationRuleService $tourAllocationRuleService
    ) {
    }

    public function createManualBooking(User $viewer, array $data): Booking
    {
        $tenantId = $viewer->isSuperAdmin()
            ? (int) $data['on_behalf_tenant_id']
            : $viewer->tenant_id;

        $tour = Tour::query()
            ->where('id', (int) $data['tour_id'])
            ->where('tenant_id', $tenantId)
            ->where('is_active', true)
            ->first();
        if (! $tour) {
            throw ValidationException::withMessages([
                'tour_id' => 'Tour tidak valid atau sudah diarsipkan.',
            ]);
        }

        $status = (string) $data['status'];
        $participants = (int) $data['participants'];
        $tourStartAt = CarbonImmutable::parse((string) $data['tour_start_at']);
        $canViewRevenue = $viewer->isAdmin();

        $net = 0.0;
        $gross = 0.0;
        $commission = 0.0;
        $currency = 'IDR';
        $fxRate = null;
        if ($canViewRevenue) {
            $net = (float) ($data['net_amount'] ?? 0);
            $gross = $net;
            $commission = 0.0;
            $revenueAmount = $net;
        } else {
            $revenueAmount = 0.0;
        }

        $booking = DB::transaction(function () use (
            $tenantId,
            $data,
            $tour,
            $status,
            $participants,
            $tourStartAt,
            $canViewRevenue,
            $currency,
            $gross,
            $commission,
            $net,
            $fxRate,
            $revenueAmount
        ) {
            $customer = $this->resolveManualCustomer($tenantId, $data);

            // Lock tour row to reduce concurrent create race on same tour/date.
            Tour::query()->whereKey($tour->id)->lockForUpdate()->first();
            $this->tourCapacityService->assertCanBook($tour, $tourStartAt, $participants);

            $booking = Booking::query()->create([
                'tenant_id' => $tenantId,
                'user_id' => null,
                'customer_id' => $customer->id,
                'tour_id' => $tour->id,
                'tour_name' => $tour->name,
                'customer_name' => $customer->full_name,
                'customer_email' => $customer->email,
                'customer_phone' => $customer->phone,
                'tour_start_at' => $tourStartAt->toDateTimeString(),
                'location' => $data['location'] ?? null,
                'guide_name' => $data['guide_name'] ?? null,
                'status' => $status,
                'booking_source' => 'manual',
                'channel' => 'MANUAL',
                'channel_order_id' => $canViewRevenue ? ($data['channel_order_id'] ?? null) : null,
                'currency' => $currency,
                'gross_amount' => $gross,
                'commission_amount' => $commission,
                'net_amount' => $net,
                'fx_rate_to_idr' => $fxRate,
                'revenue_amount' => $revenueAmount,
                'participants' => $participants,
                'notes' => $data['notes'] ?? null,
                'internal_notes' => $data['internal_notes'] ?? null,
                'confirmed_at' => $status === 'confirmed' ? now() : null,
            ]);

            $this->logStatusEvent($booking, 'init', $status, 'operator', 'manual_create', 'web');

            return $booking;
        });

        return $booking->fresh(['customer']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function resolveManualCustomer(?int $tenantId, array $data): Customer
    {
        $fullName = (string) $data['customer_name'];
        $email = isset($data['customer_email']) ? trim((string) $data['customer_email']) : '';
        $email = $email === '' ? null : $email;
        $phone = isset($data['customer_phone']) ? trim((string) $data['customer_phone']) : '';
        $phone = $phone === '' ? null : $phone;

        $query = Customer::query()->where('tenant_id', $tenantId);

        if ($phone !== null) {
            $existing = (clone $query)->where('phone', $phone)->first();
            if ($existing !== null) {
                $existing->update(array_filter([
                    'full_name' => $fullName,
                    'email' => $email ?? $existing->email,
                ], fn ($v) => $v !== null));

                return $existing->refresh();
            }
        }

        if ($email !== null) {
            $existing = (clone $query)->where('email', $email)->first();
            if ($existing !== null) {
                $existing->update(array_filter([
                    'full_name' => $fullName,
                    'phone' => $phone ?? $existing->phone,
                ], fn ($v) => $v !== null));

                return $existing->refresh();
            }
        }

        return Customer::query()->create([
            'tenant_id' => $tenantId,
            'full_name' => $fullName,
            'email' => $email,
            'phone' => $phone,
            'external_source' => 'manual',
        ]);
    }

    public function paginate(array $filters, User $viewer): LengthAwarePaginator
    {
        $perPage = (int) ($filters['per_page'] ?? 15);
        $sortBy = $filters['sort_by'] ?? 'tour_start_at';
        $sortDir = $filters['sort_dir'] ?? 'asc';

        if (! in_array($sortBy, ['tour_start_at', 'customer_name', 'status', 'created_at'], true)) {
            $sortBy = 'tour_start_at';
        }

        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'asc';
        }

        $allowedStatuses = ['standby', 'confirmed', 'pending', 'cancelled'];

        return $viewer->bookingsVisibleQuery()
            ->with('customer')
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('tour_name', 'like', '%'.$search.'%')
                        ->orWhere('customer_name', 'like', '%'.$search.'%');
                });
            })
            ->when($filters['status'] ?? null, function ($query, string $status) use ($allowedStatuses): void {
                if (str_contains($status, ',')) {
                    $parts = array_values(array_filter(array_map('trim', explode(',', $status))));
                    $safe = array_values(array_intersect($parts, $allowedStatuses));
                    if ($safe !== []) {
                        $query->whereIn('status', $safe);
                    }

                    return;
                }

                if (in_array($status, $allowedStatuses, true)) {
                    $query->where('status', $status);
                }
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }

    public function updateStatus(Booking $booking, string $status): Booking
    {
        $oldStatus = $booking->status;
        if ($status === 'confirmed' && $oldStatus !== 'confirmed') {
            $this->tourAllocationRuleService->assertReadyForConfirmedStatus($booking);
        }
        $booking->update(['status' => $status]);
        $this->logStatusEvent($booking, $oldStatus, $status, 'operator', 'manual_update', 'api');

        return $booking->refresh();
    }

    /**
     * Distinct assignee labels already used on visible bookings (for autocomplete).
     *
     * @return list<string>
     */
    public function assigneeNameSuggestions(User $viewer, ?string $q = null): array
    {
        $q = $q !== null ? trim($q) : '';
        if (strlen($q) > 200) {
            $q = substr($q, 0, 200);
        }

        $safe = str_replace(['%', '_'], '', $q);

        return $viewer->bookingsVisibleQuery()
            ->whereNotNull('assigned_to_name')
            ->where('assigned_to_name', '!=', '')
            ->when($safe !== '', function ($query) use ($safe): void {
                $query->where('assigned_to_name', 'like', '%'.$safe.'%');
            })
            ->select('assigned_to_name')
            ->distinct()
            ->orderBy('assigned_to_name')
            ->limit(30)
            ->pluck('assigned_to_name')
            ->values()
            ->all();
    }

    public function updateLocalFields(Booking $booking, array $payload, User $viewer): Booking
    {
        $canManageRevenue = $viewer->isAdmin();
        $oldChannel = (string) ($booking->channel ?? '');
        $oldBookingSource = (string) ($booking->booking_source ?? 'manual');
        $netAmount = array_key_exists('net_amount', $payload)
            ? (float) $payload['net_amount']
            : (float) $booking->net_amount;
        $grossAmount = array_key_exists('gross_amount', $payload)
            ? (float) $payload['gross_amount']
            : (float) $booking->gross_amount;
        $commissionAmount = array_key_exists('commission_amount', $payload)
            ? (float) $payload['commission_amount']
            : (float) $booking->commission_amount;
        $fxRateToIdr = array_key_exists('fx_rate_to_idr', $payload)
            ? ($payload['fx_rate_to_idr'] !== null ? (float) $payload['fx_rate_to_idr'] : null)
            : ($booking->fx_rate_to_idr !== null ? (float) $booking->fx_rate_to_idr : null);

        $baseRevenue = array_key_exists('revenue_amount', $payload)
            ? (float) $payload['revenue_amount']
            : ($netAmount > 0 ? $netAmount : max(0, $grossAmount - $commissionAmount));
        $revenueAmount = $fxRateToIdr !== null
            ? $baseRevenue * $fxRateToIdr
            : $baseRevenue;

        $updatePayload = [
            'notes' => $payload['notes'] ?? $booking->notes,
            'location' => $payload['location'] ?? $booking->location,
            'guide_name' => $payload['guide_name'] ?? $booking->guide_name,
            'internal_notes' => $payload['internal_notes'] ?? $booking->internal_notes,
            'assigned_to_name' => $payload['assigned_to_name'] ?? $booking->assigned_to_name,
            'tags' => $payload['tags'] ?? $booking->tags,
            'needs_attention' => $payload['needs_attention'] ?? $booking->needs_attention,
        ];

        if ($canManageRevenue) {
            $nextChannel = $payload['channel'] ?? $booking->channel;
            $normalizedChannel = strtolower((string) $nextChannel);
            $nextSource = in_array($normalizedChannel, ['manual', 'direct', ''], true) ? 'manual' : 'ota';

            $updatePayload['channel'] = $nextChannel;
            $updatePayload['booking_source'] = $nextSource;
            $updatePayload['channel_order_id'] = $payload['channel_order_id'] ?? $booking->channel_order_id;
            $updatePayload['currency'] = isset($payload['currency']) ? strtoupper((string) $payload['currency']) : $booking->currency;
            $updatePayload['gross_amount'] = $grossAmount;
            $updatePayload['commission_amount'] = $commissionAmount;
            $updatePayload['net_amount'] = $netAmount;
            $updatePayload['fx_rate_to_idr'] = $fxRateToIdr;
            $updatePayload['revenue_amount'] = $revenueAmount;
            $updatePayload['pricing_payload_json'] = $payload['pricing_payload_json'] ?? $booking->pricing_payload_json;
        }

        $booking->update($updatePayload);

        if (
            $canManageRevenue
            && (
                strtolower($oldChannel) !== strtolower((string) ($booking->channel ?? ''))
                || strtolower($oldBookingSource) !== strtolower((string) ($booking->booking_source ?? 'manual'))
            )
        ) {
            $this->tenantAuditLogService->record(
                tenantId: (int) $booking->tenant_id,
                actorUserId: (int) $viewer->id,
                eventType: 'booking.source_or_channel.updated',
                entityType: 'booking',
                entityId: (int) $booking->id,
                tourId: $booking->tour_id ? (int) $booking->tour_id : null,
                serviceDate: $booking->tour_start_at,
                context: [
                    'old_channel' => $oldChannel,
                    'new_channel' => $booking->channel,
                    'old_booking_source' => $oldBookingSource,
                    'new_booking_source' => $booking->booking_source,
                ]
            );
        }

        return $booking->refresh();
    }

    /**
     * @return array{0: Booking, 1: string}
     */
    public function generateConfirmationToken(Booking $booking): array
    {
        $plain = Str::random(48);
        $booking->update([
            'confirmation_token' => null,
            'confirmation_token_hash' => hash('sha256', $plain),
            'confirmation_token_expires_at' => now()->addDays(7),
        ]);

        return [$booking->refresh(), $plain];
    }

    /**
     * @param  string  $action  confirm|cancel|reschedule
     */
    public function respondViaMagicLink(Booking $booking, string $action): Booking
    {
        if ($booking->customer_response !== null) {
            return $booking->refresh();
        }

        $oldStatus = $booking->status;

        return match ($action) {
            'confirm' => $this->magicLinkConfirm($booking, $oldStatus),
            'cancel' => $this->magicLinkCancel($booking, $oldStatus),
            'reschedule' => $this->magicLinkReschedule($booking, $oldStatus),
            default => $booking->refresh(),
        };
    }

    public function confirmByLink(Booking $booking): Booking
    {
        return $this->respondViaMagicLink($booking, 'confirm');
    }

    private function magicLinkConfirm(Booking $booking, string $oldStatus): Booking
    {
        if ($booking->status === 'cancelled') {
            return $booking->refresh();
        }

        $booking->update([
            'status' => 'confirmed',
            'confirmed_at' => $booking->confirmed_at ?? now(),
            'customer_response' => 'confirmed',
            'customer_responded_at' => now(),
        ]);

        if ($oldStatus !== 'confirmed') {
            $this->logStatusEvent($booking, $oldStatus, 'confirmed', 'customer', 'magic_link_confirm', 'web');
        }

        return $booking->refresh();
    }

    private function magicLinkCancel(Booking $booking, string $oldStatus): Booking
    {
        $booking->update([
            'status' => 'cancelled',
            'customer_response' => 'cancelled',
            'customer_responded_at' => now(),
        ]);

        if ($oldStatus !== 'cancelled') {
            $this->logStatusEvent($booking, $oldStatus, 'cancelled', 'customer', 'magic_link_cancel', 'web');
        }

        return $booking->refresh();
    }

    private function magicLinkReschedule(Booking $booking, string $oldStatus): Booking
    {
        if ($booking->status === 'cancelled') {
            return $booking->refresh();
        }

        $payload = [
            'needs_attention' => true,
            'customer_response' => 'reschedule_requested',
            'customer_responded_at' => now(),
        ];

        if ($oldStatus === 'confirmed') {
            $payload['status'] = 'pending';
            $payload['confirmed_at'] = null;
        }

        $this->createRescheduleRequest(
            $booking,
            'customer',
            'magic_link',
            'Customer requested a new departure schedule via confirmation link.'
        );

        $booking->update($payload);
        $booking->refresh();

        $this->logStatusEvent(
            $booking,
            $oldStatus,
            $booking->status,
            'customer',
            'magic_link_reschedule',
            'web',
            null,
            ['customer_response' => 'reschedule_requested']
        );

        return $booking;
    }

    private function createRescheduleRequest(
        Booking $booking,
        string $requestedBy,
        ?string $requestSource = null,
        ?string $notes = null
    ): void {
        BookingReschedule::query()->create([
            'booking_id' => $booking->id,
            'requested_by' => $requestedBy,
            'request_source' => $requestSource,
            'workflow_status' => 'requested',
            'old_tour_start_at' => $booking->tour_start_at,
            'notes' => $notes,
        ]);
    }

    public function updateRescheduleWorkflow(Booking $booking, BookingReschedule $reschedule, array $payload, int $viewerId): Booking
    {
        return DB::transaction(function () use ($booking, $reschedule, $payload, $viewerId) {
            $newWorkflowStatus = (string) $payload['workflow_status'];
            $oldBookingStatus = (string) $booking->status;

            $rescheduleUpdate = [
                'workflow_status' => $newWorkflowStatus,
                'requested_tour_start_at' => $payload['requested_tour_start_at'] ?? $reschedule->requested_tour_start_at,
                'final_tour_start_at' => $payload['final_tour_start_at'] ?? $reschedule->final_tour_start_at,
                'requested_reason' => $payload['requested_reason'] ?? $reschedule->requested_reason,
                'notes' => $payload['notes'] ?? $reschedule->notes,
                'reviewed_by_user_id' => $viewerId,
                'reviewed_at' => now(),
            ];

            if ($newWorkflowStatus === 'completed') {
                $rescheduleUpdate['completed_at'] = now();
            }

            $reschedule->update($rescheduleUpdate);

            $bookingUpdate = [];
            if (in_array($newWorkflowStatus, ['rejected', 'completed'], true)) {
                $bookingUpdate['needs_attention'] = false;
                $bookingUpdate['customer_response'] = null;
            } else {
                $bookingUpdate['needs_attention'] = true;
                $bookingUpdate['customer_response'] = 'reschedule_requested';
            }

            if ($newWorkflowStatus === 'completed') {
                $finalStartAt = $reschedule->fresh()->final_tour_start_at;
                if ($finalStartAt !== null) {
                    if ($booking->tour_id !== null) {
                        $tour = Tour::query()->whereKey($booking->tour_id)->lockForUpdate()->first();
                        if ($tour) {
                            $this->tourCapacityService->assertCanBook(
                                $tour,
                                CarbonImmutable::parse((string) $finalStartAt),
                                (int) $booking->participants,
                                (int) $booking->id
                            );
                        }
                    }
                    $bookingUpdate['tour_start_at'] = $finalStartAt;
                }
                $bookingUpdate['status'] = 'confirmed';
                $bookingUpdate['confirmed_at'] = $booking->confirmed_at ?? now();
            }

            if ($newWorkflowStatus === 'completed' && ($bookingUpdate['status'] ?? null) === 'confirmed') {
                $previewStart = $bookingUpdate['tour_start_at'] ?? $booking->tour_start_at;
                $preview = $booking->replicate();
                $preview->tour_start_at = $previewStart;
                $preview->status = 'confirmed';
                $this->tourAllocationRuleService->assertReadyForConfirmedStatus($preview);
            }

            $booking->update($bookingUpdate);
            $booking->refresh();

            $this->logStatusEvent(
                $booking,
                $oldBookingStatus,
                (string) $booking->status,
                'operator',
                'reschedule_workflow_'.$newWorkflowStatus,
                'web',
                null,
                [
                    'reschedule_id' => $reschedule->id,
                    'workflow_status' => $newWorkflowStatus,
                    'requested_tour_start_at' => optional($reschedule->requested_tour_start_at)?->toIso8601String(),
                    'final_tour_start_at' => optional($reschedule->final_tour_start_at)?->toIso8601String(),
                    'reviewed_by_user_id' => $viewerId,
                ]
            );

            return $booking;
        });
    }

    private function logStatusEvent(
        Booking $booking,
        string $oldStatus,
        string $newStatus,
        string $changedBy,
        ?string $reason = null,
        ?string $source = null,
        ?string $sourceMessageId = null,
        ?array $metadata = null
    ): void {
        BookingStatusEvent::query()->create([
            'booking_id' => $booking->id,
            'old_status' => $oldStatus,
            'new_status' => $newStatus,
            'changed_by' => $changedBy,
            'reason' => $reason,
            'source' => $source,
            'source_message_id' => $sourceMessageId,
            'metadata' => $metadata,
        ]);
    }
}
