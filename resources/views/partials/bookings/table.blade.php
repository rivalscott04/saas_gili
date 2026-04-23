<div class="table-responsive table-card mb-1">
    <table class="table table-nowrap align-middle">
        <thead class="text-muted table-light">
            <tr class="text-uppercase">
                <th data-col-key="booking_id">{{ __('translation.booking-id') }}</th>
                <th data-col-key="guest_name">{{ __('translation.guest-name') }}</th>
                <th data-col-key="tour_package">{{ __('translation.tour-package') }}</th>
                <th data-col-key="departure_date">{{ __('translation.departure-date') }}</th>
                <th data-col-key="location">{{ __('translation.location') }}</th>
                <th data-col-key="guide">{{ __('translation.guide') }}</th>
                <th data-col-key="pax">{{ __('translation.pax') }}</th>
                @if(($canViewRevenue ?? false) === true)
                    <th data-col-key="channel">{{ __('translation.order-source') }}</th>
                    <th data-col-key="net_revenue">{{ __('translation.net-revenue') }}</th>
                @endif
                <th data-col-key="status">{{ __('translation.status') }}</th>
                <th data-col-key="assigned_guide">{{ __('translation.assigned-guide') }}</th>
                <th data-col-key="attention">{{ __('translation.attention') }}</th>
                <th>{{ __('translation.action') }}</th>
            </tr>
        </thead>
        <tbody>
            @forelse($bookings as $booking)
                @php
                    $status = strtolower((string) $booking->status);
                    $statusClass = match ($status) {
                        'confirmed' => 'bg-success-subtle text-success',
                        'cancelled' => 'bg-danger-subtle text-danger',
                        'standby' => 'bg-secondary-subtle text-secondary',
                        'pending' => 'bg-warning-subtle text-warning',
                        'on tour', 'on_tour' => 'bg-info-subtle text-info',
                        default => 'bg-primary-subtle text-primary',
                    };
                    $statusLabel = match ($status) {
                        'confirmed' => __('translation.confirmed'),
                        'cancelled' => __('translation.cancelled'),
                        'standby' => __('translation.standby'),
                        'pending' => __('translation.pending'),
                        'on tour', 'on_tour' => __('translation.on-tour'),
                        default => __('translation.unknown'),
                    };
                @endphp
                <tr
                    data-booking-row
                    data-tenant-id="{{ $booking->tenant_id }}"
                    data-status="{{ str_replace(' ', '_', strtolower((string) $booking->status)) }}"
                    data-response="{{ strtolower((string) $booking->customer_response) }}"
                    data-reschedule-workflow="{{ strtolower((string) ($booking->latestReschedule?->workflow_status ?? '')) }}"
                    data-search="{{ strtolower(($booking->customer?->full_name ?? $booking->customer_name ?? '').' '.($booking->tour_name ?? '').' '.($booking->guide_name ?? '')) }}"
                    data-start-date="{{ optional($booking->tour_start_at)?->format('Y-m-d') }}"
                >
                    <td class="fw-medium" data-col-key="booking_id">#BK{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}</td>
                    <td data-col-key="guest_name">{{ $booking->customer?->full_name ?? $booking->customer_name ?? '-' }}</td>
                    <td data-col-key="tour_package">{{ $booking->tour_name ?? '-' }}</td>
                    <td data-col-key="departure_date">
                        @if($booking->tour_start_at)
                            {{ $booking->tour_start_at->format('d M Y, H:i') }}
                        @else
                            -
                        @endif
                    </td>
                    <td data-col-key="location">{{ $booking->location ?? '-' }}</td>
                    <td data-col-key="guide">{{ $booking->guide_name ?? '-' }}</td>
                    <td data-col-key="pax">{{ $booking->participants ?? '-' }}</td>
                    @if(($canViewRevenue ?? false) === true)
                        <td data-col-key="channel">{{ strtoupper((string) ($booking->channel ?? '-')) }}</td>
                        <td data-col-key="net_revenue">
                            @php
                                $originalCurrency = strtoupper((string) ($booking->currency ?? 'IDR'));
                                $originalNet = (float) ($booking->net_amount ?? 0);
                                $fxRate = (float) ($booking->fx_rate_to_idr ?? 0);
                                $idrNet = (float) ($booking->revenue_amount ?? 0);
                                if ($idrNet <= 0 && $originalCurrency === 'IDR') {
                                    $idrNet = $originalNet;
                                } elseif ($idrNet <= 0 && $fxRate > 0) {
                                    $idrNet = $originalNet * $fxRate;
                                }
                            @endphp
                            <div class="fw-semibold">
                                {{ $originalCurrency }} {{ number_format($originalNet, 2) }}
                            </div>
                            <small class="text-muted d-block">
                                {{ __('translation.net-idr') }}: IDR {{ number_format($idrNet, 0) }}
                            </small>
                        </td>
                    @endif
                    <td data-col-key="status">
                        <span class="badge text-uppercase {{ $statusClass }}">{{ $statusLabel }}</span>
                        @if(!empty($bookingAllocationReadinessWarnings[$booking->id] ?? null))
                            <div class="mt-1">
                                <span class="badge bg-warning-subtle text-warning text-wrap text-start"
                                    title="{{ $bookingAllocationReadinessWarnings[$booking->id] }}">
                                    <i class="ri-ship-line align-bottom me-1"></i>{{ __('translation.resource') }}
                                </span>
                            </div>
                        @endif
                    </td>
                    <td data-col-key="assigned_guide">{{ $booking->assigned_to_name ?? '-' }}</td>
                    <td data-col-key="attention">
                        @if($booking->needs_attention)
                            <span class="badge bg-danger-subtle text-danger"><i class="ri-alert-line align-bottom me-1"></i>{{ __('translation.needs-follow-up') }}</span>
                        @else
                            <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line align-bottom me-1"></i>{{ __('translation.normal') }}</span>
                        @endif
                        @if(($booking->reschedules_count ?? 0) > 0)
                            <div class="mt-1">
                                <span class="badge bg-warning-subtle text-warning">
                                    <i class="ri-calendar-event-line align-bottom me-1"></i>{{ __('translation.reschedule') }} x{{ $booking->reschedules_count }}
                                </span>
                            </div>
                            @if($booking->latestReschedule)
                                @php
                                    $workflowStatusKey = strtolower((string) $booking->latestReschedule->workflow_status);
                                    $workflowStatusLabel = \Illuminate\Support\Facades\Lang::has('translation.'.$workflowStatusKey)
                                        ? __('translation.'.$workflowStatusKey)
                                        : str_replace('_', ' ', $workflowStatusKey);
                                @endphp
                                <small class="text-muted d-block mt-1">
                                    {{ __('translation.workflow') }}:
                                    <span class="fw-semibold text-capitalize">
                                        {{ $workflowStatusLabel }}
                                    </span>
                                </small>
                            @endif
                            <small class="text-muted d-block mt-1">
                                {{ __('translation.last-request') }}:
                                {{ $booking->reschedules_max_created_at ? \Illuminate\Support\Carbon::parse($booking->reschedules_max_created_at)->format('d M Y, H:i') : '-' }}
                            </small>
                        @endif
                    </td>
                    <td>
                        <button
                            type="button"
                            class="btn btn-sm {{ strtolower((string) $booking->status) === 'confirmed' ? 'btn-soft-secondary' : 'btn-soft-success' }} js-open-reminder-modal"
                            data-bs-toggle="modal"
                            data-bs-target="#bookingReminderModal"
                            data-booking-id="{{ $booking->id }}"
                            data-booking-name="{{ $booking->customer?->full_name ?? $booking->customer_name ?? '-' }}"
                            data-booking-code="#BK{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}"
                            {{ (strtolower((string) $booking->status) === 'confirmed' || !($canSendReminder ?? true)) ? 'disabled' : '' }}
                        >
                            <i class="ri-whatsapp-line align-bottom me-1"></i>{{ __('translation.reminder') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-soft-info js-open-reminder-history-modal mt-1"
                            data-bs-toggle="modal"
                            data-bs-target="#bookingReminderHistoryModal"
                            data-booking-id="{{ $booking->id }}"
                            data-booking-code="#BK{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}"
                        >
                            <i class="ri-history-line align-bottom me-1"></i>{{ __('translation.history') }}
                        </button>
                        <button
                            type="button"
                            class="btn btn-sm btn-soft-warning js-open-reschedule-modal mt-1"
                            data-bs-toggle="modal"
                            data-bs-target="#bookingRescheduleModal"
                            data-booking-id="{{ $booking->id }}"
                            data-booking-name="{{ $booking->customer?->full_name ?? $booking->customer_name ?? '-' }}"
                            data-booking-code="#BK{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}"
                            data-booking-start="{{ optional($booking->tour_start_at)?->toIso8601String() }}"
                            {{ !($canManageReschedule ?? true) ? 'disabled' : '' }}
                        >
                            <i class="ri-calendar-check-line align-bottom me-1"></i>{{ __('translation.reschedule') }}
                        </button>
                        @if(auth()->user()?->isAdmin())
                            <button
                                type="button"
                                class="btn btn-sm btn-soft-primary js-open-resource-allocation-modal mt-1"
                                data-bs-toggle="modal"
                                data-bs-target="#bookingResourceAllocationModal"
                                data-booking-id="{{ $booking->id }}"
                                data-booking-name="{{ $booking->customer?->full_name ?? $booking->customer_name ?? '-' }}"
                                data-booking-code="#BK{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}"
                                data-booking-date="{{ optional($booking->tour_start_at)?->toDateString() }}"
                            >
                                <i class="ri-archive-stack-line align-bottom me-1"></i>{{ __('translation.resource') }}
                            </button>
                        @endif
                    </td>
                </tr>
            @empty
                <tr>
                    <td colspan="{{ ($canViewRevenue ?? false) ? 13 : 11 }}" class="text-center py-4 text-muted">
                        {{ __('translation.no-booking-data-available') }}
                    </td>
                </tr>
            @endforelse
        </tbody>
    </table>
</div>
<div class="alert alert-info mt-3 mb-0">
    {{ __('translation.booking-data-sync-note') }}
</div>
@if ($bookings->count() > 0)
    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
        <small class="text-muted">
            {{ __('translation.showing-range-of-total-bookings', ['from' => $bookings->firstItem(), 'to' => $bookings->lastItem(), 'total' => $bookings->total()]) }}
        </small>
        {{ $bookings->links() }}
    </div>
@endif
<div id="bookingTableEmptyState" class="noresult text-center py-4" style="display: none;">
    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px"></lord-icon>
    <h5 class="mt-2">{{ __('translation.no-result-found') }}</h5>
    <p class="text-muted mb-0">{{ __('translation.no-booking-selected-status') }}</p>
</div>
