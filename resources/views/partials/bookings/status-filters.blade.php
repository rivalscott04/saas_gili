@php
    $bookingRows = collect($bookings->items());
    $statusCounts = [
        'all' => $bookingRows->count(),
        'reschedule_requested' => $bookingRows->where('customer_response', 'reschedule_requested')->count(),
        'confirmed' => $bookingRows->where('status', 'confirmed')->count(),
        'on_tour' => $bookingRows->where('status', 'on_tour')->count() + $bookingRows->where('status', 'on tour')->count(),
        'standby' => $bookingRows->where('status', 'standby')->count(),
        'pending' => $bookingRows->where('status', 'pending')->count(),
        'cancelled' => $bookingRows->where('status', 'cancelled')->count(),
    ];
    $workflowCounts = [
        'requested' => $bookingRows->filter(fn ($booking) => strtolower((string) ($booking->latestReschedule?->workflow_status ?? '')) === 'requested')->count(),
        'reviewed' => $bookingRows->filter(fn ($booking) => strtolower((string) ($booking->latestReschedule?->workflow_status ?? '')) === 'reviewed')->count(),
        'approved' => $bookingRows->filter(fn ($booking) => strtolower((string) ($booking->latestReschedule?->workflow_status ?? '')) === 'approved')->count(),
        'rejected' => $bookingRows->filter(fn ($booking) => strtolower((string) ($booking->latestReschedule?->workflow_status ?? '')) === 'rejected')->count(),
        'completed' => $bookingRows->filter(fn ($booking) => strtolower((string) ($booking->latestReschedule?->workflow_status ?? '')) === 'completed')->count(),
        'no_request' => $bookingRows->filter(fn ($booking) => ($booking->latestReschedule?->workflow_status ?? null) === null)->count(),
    ];
@endphp
<ul class="nav nav-tabs nav-tabs-custom nav-success mb-3" role="tablist" id="bookingStatusTabs">
    <li class="nav-item">
        <a class="nav-link active py-3" href="#" data-status-filter="all">
            <i class="ri-list-check me-1 align-bottom"></i>
            {{ __('translation.all') }}
            <span class="badge bg-secondary-subtle text-secondary align-middle ms-1">{{ $statusCounts['all'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-3" href="#" data-status-filter="reschedule_requested">
            <i class="ri-calendar-event-line me-1 align-bottom"></i>
            {{ __('translation.reschedule-request') }}
            <span class="badge bg-warning-subtle text-warning align-middle ms-1">{{ $statusCounts['reschedule_requested'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-3" href="#" data-status-filter="confirmed">
            <i class="ri-checkbox-circle-line me-1 align-bottom"></i>
            {{ __('translation.confirmed') }}
            <span class="badge bg-success-subtle text-success align-middle ms-1">{{ $statusCounts['confirmed'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-3" href="#" data-status-filter="on_tour">
            <i class="ri-route-line me-1 align-bottom"></i>
            {{ __('translation.on-tour') }}
            <span class="badge bg-info-subtle text-info align-middle ms-1">{{ $statusCounts['on_tour'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-3" href="#" data-status-filter="standby">
            <i class="ri-time-line me-1 align-bottom"></i>
            {{ __('translation.standby') }}
            <span class="badge bg-secondary-subtle text-secondary align-middle ms-1">{{ $statusCounts['standby'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-3" href="#" data-status-filter="pending">
            <i class="ri-error-warning-line me-1 align-bottom"></i>
            {{ __('translation.pending') }}
            <span class="badge bg-warning-subtle text-warning align-middle ms-1">{{ $statusCounts['pending'] }}</span>
        </a>
    </li>
    <li class="nav-item">
        <a class="nav-link py-3" href="#" data-status-filter="cancelled">
            <i class="ri-close-circle-line me-1 align-bottom"></i>
            {{ __('translation.cancelled') }}
            <span class="badge bg-danger-subtle text-danger align-middle ms-1">{{ $statusCounts['cancelled'] }}</span>
        </a>
    </li>
</ul>
<div class="d-flex flex-wrap gap-2 mb-3" id="rescheduleWorkflowQuickChips">
    <button type="button" class="btn btn-sm btn-soft-secondary active" data-workflow-chip="all">{{ __('translation.all-workflow') }} (<span data-workflow-chip-count>{{ $bookingRows->count() }}</span>)</button>
    <button type="button" class="btn btn-sm btn-soft-warning" data-workflow-chip="requested">{{ __('translation.requested') }} (<span data-workflow-chip-count>{{ $workflowCounts['requested'] }}</span>)</button>
    <button type="button" class="btn btn-sm btn-soft-info" data-workflow-chip="reviewed">{{ __('translation.reviewed') }} (<span data-workflow-chip-count>{{ $workflowCounts['reviewed'] }}</span>)</button>
    <button type="button" class="btn btn-sm btn-soft-primary" data-workflow-chip="approved">{{ __('translation.approved') }} (<span data-workflow-chip-count>{{ $workflowCounts['approved'] }}</span>)</button>
    <button type="button" class="btn btn-sm btn-soft-danger" data-workflow-chip="rejected">{{ __('translation.rejected') }} (<span data-workflow-chip-count>{{ $workflowCounts['rejected'] }}</span>)</button>
    <button type="button" class="btn btn-sm btn-soft-success" data-workflow-chip="completed">{{ __('translation.completed') }} (<span data-workflow-chip-count>{{ $workflowCounts['completed'] }}</span>)</button>
    <button type="button" class="btn btn-sm btn-soft-dark" data-workflow-chip="no_request">{{ __('translation.no-request') }} (<span data-workflow-chip-count>{{ $workflowCounts['no_request'] }}</span>)</button>
</div>
<div class="row g-3 mb-4">
    <div class="col-xxl-4 col-sm-12">
        <div class="search-box">
            <input type="text" class="form-control search bg-light border-light" id="bookingKeywordFilter" placeholder="{{ __('translation.search-guest-package-guide') }}">
            <i class="ri-search-line search-icon"></i>
        </div>
    </div>
    <div class="col-xxl-3 col-sm-6">
        <input type="text" class="form-control bg-light border-light" id="bookingDateRangeFilter" data-provider="flatpickr" data-date-format="d M, Y" data-range-date="true" placeholder="{{ __('translation.select-departure-date-range') }}">
    </div>
    <div class="col-xxl-5 col-sm-12">
        <div class="d-flex gap-2">
            <button type="button" class="btn btn-soft-secondary" id="resetBookingFilters" title="{{ __('translation.reset-filters') }}">
                <i class="ri-refresh-line"></i>
            </button>
            <div class="dropdown">
                <button class="btn btn-soft-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="{{ __('translation.show-hide-columns') }}">
                    <i class="ri-table-line"></i>
                </button>
                <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px;" id="bookingColumnToggleMenu">
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="booking_id" checked> {{ __('translation.booking-id') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="guest_name" checked> {{ __('translation.guest-name') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="tour_package" checked> {{ __('translation.tour-package') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="departure_date" checked> {{ __('translation.departure-date') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="location" checked> {{ __('translation.location') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="guide" checked> {{ __('translation.guide') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="pax" checked> {{ __('translation.pax') }}</label></li>
                    @if(($canViewRevenue ?? false) === true)
                        <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="channel" checked> {{ __('translation.order-source') }}</label></li>
                        <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="net_revenue" checked> {{ __('translation.net-revenue') }}</label></li>
                    @endif
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="status" checked> {{ __('translation.status') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="assigned_guide" checked> {{ __('translation.assigned-guide') }}</label></li>
                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="attention" checked> {{ __('translation.attention') }}</label></li>
                </ul>
            </div>
        </div>
    </div>
</div>
