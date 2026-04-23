@extends('layouts.master')
@section('title')
Booking List
@endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
Tour Operations
@endslot
@slot('title')
Booking List
@endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            <div class="card-header border-0">
                <div class="row align-items-center gy-3">
                    <div class="col-sm">
                        <h5 class="card-title mb-0">Booking Management</h5>
                    </div>
                    <div class="col-sm-auto">
                        <div class="d-flex gap-1 flex-wrap">
                            @can('create', \App\Models\Booking::class)
                                <a href="{{ route('bookings.manual.create') }}" class="btn btn-soft-success"><i class="ri-add-line align-bottom me-1"></i> Create Booking</a>
                            @else
                                <span class="btn btn-soft-secondary disabled" title="Tidak ada izin akses booking"><i class="ri-add-line align-bottom me-1"></i> Create Booking</span>
                            @endcan
                            <a href="{{ route('bookings.recap') }}" class="btn btn-primary"><i class="ri-bar-chart-box-line align-bottom me-1"></i> Revenue Recap</a>
                            <a href="apps-bookings-calendar" class="btn btn-info"><i class="ri-calendar-2-line align-bottom me-1"></i> Booking Calendar</a>
                        </div>
                    </div>
                </div>
            </div>
            <div class="card-body pt-0">
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
                            All
                            <span class="badge bg-secondary-subtle text-secondary align-middle ms-1">{{ $statusCounts['all'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3" href="#" data-status-filter="reschedule_requested">
                            <i class="ri-calendar-event-line me-1 align-bottom"></i>
                            Reschedule Request
                            <span class="badge bg-warning-subtle text-warning align-middle ms-1">{{ $statusCounts['reschedule_requested'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3" href="#" data-status-filter="confirmed">
                            <i class="ri-checkbox-circle-line me-1 align-bottom"></i>
                            Confirmed
                            <span class="badge bg-success-subtle text-success align-middle ms-1">{{ $statusCounts['confirmed'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3" href="#" data-status-filter="on_tour">
                            <i class="ri-route-line me-1 align-bottom"></i>
                            On Tour
                            <span class="badge bg-info-subtle text-info align-middle ms-1">{{ $statusCounts['on_tour'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3" href="#" data-status-filter="standby">
                            <i class="ri-time-line me-1 align-bottom"></i>
                            Standby
                            <span class="badge bg-secondary-subtle text-secondary align-middle ms-1">{{ $statusCounts['standby'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3" href="#" data-status-filter="pending">
                            <i class="ri-error-warning-line me-1 align-bottom"></i>
                            Pending
                            <span class="badge bg-warning-subtle text-warning align-middle ms-1">{{ $statusCounts['pending'] }}</span>
                        </a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link py-3" href="#" data-status-filter="cancelled">
                            <i class="ri-close-circle-line me-1 align-bottom"></i>
                            Cancelled
                            <span class="badge bg-danger-subtle text-danger align-middle ms-1">{{ $statusCounts['cancelled'] }}</span>
                        </a>
                    </li>
                </ul>
                <div class="d-flex flex-wrap gap-2 mb-3" id="rescheduleWorkflowQuickChips">
                    <button type="button" class="btn btn-sm btn-soft-secondary active" data-workflow-chip="all">All workflow (<span data-workflow-chip-count>{{ $bookingRows->count() }}</span>)</button>
                    <button type="button" class="btn btn-sm btn-soft-warning" data-workflow-chip="requested">Requested (<span data-workflow-chip-count>{{ $workflowCounts['requested'] }}</span>)</button>
                    <button type="button" class="btn btn-sm btn-soft-info" data-workflow-chip="reviewed">Reviewed (<span data-workflow-chip-count>{{ $workflowCounts['reviewed'] }}</span>)</button>
                    <button type="button" class="btn btn-sm btn-soft-primary" data-workflow-chip="approved">Approved (<span data-workflow-chip-count>{{ $workflowCounts['approved'] }}</span>)</button>
                    <button type="button" class="btn btn-sm btn-soft-danger" data-workflow-chip="rejected">Rejected (<span data-workflow-chip-count>{{ $workflowCounts['rejected'] }}</span>)</button>
                    <button type="button" class="btn btn-sm btn-soft-success" data-workflow-chip="completed">Completed (<span data-workflow-chip-count>{{ $workflowCounts['completed'] }}</span>)</button>
                    <button type="button" class="btn btn-sm btn-soft-dark" data-workflow-chip="no_request">No request (<span data-workflow-chip-count>{{ $workflowCounts['no_request'] }}</span>)</button>
                </div>
                <div class="row g-3 mb-4">
                    <div class="col-xxl-4 col-sm-12">
                        <div class="search-box">
                            <input type="text" class="form-control search bg-light border-light" id="bookingKeywordFilter" placeholder="Search guest, package, or guide...">
                            <i class="ri-search-line search-icon"></i>
                        </div>
                    </div>
                    <div class="col-xxl-3 col-sm-6">
                        <input type="text" class="form-control bg-light border-light" id="bookingDateRangeFilter" data-provider="flatpickr" data-date-format="d M, Y" data-range-date="true" placeholder="Select departure date range">
                    </div>
                    <div class="col-xxl-5 col-sm-12">
                        <div class="d-flex gap-2">
                            <button type="button" class="btn btn-soft-secondary" id="resetBookingFilters" title="Reset filters">
                                <i class="ri-refresh-line"></i>
                            </button>
                            <div class="dropdown">
                                <button class="btn btn-soft-dark" type="button" data-bs-toggle="dropdown" aria-expanded="false" title="Show/Hide Columns">
                                    <i class="ri-table-line"></i>
                                </button>
                                <ul class="dropdown-menu dropdown-menu-end p-2" style="min-width: 220px;" id="bookingColumnToggleMenu">
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="booking_id" checked> Booking ID</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="guest_name" checked> Guest Name</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="tour_package" checked> Tour Package</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="departure_date" checked> Departure Date</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="location" checked> Location</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="guide" checked> Guide</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="pax" checked> PAX</label></li>
                                    @if(($canViewRevenue ?? false) === true)
                                        <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="channel" checked> Channel</label></li>
                                        <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="net_revenue" checked> Net Revenue</label></li>
                                    @endif
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="status" checked> Status</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="assigned_guide" checked> Assigned Guide</label></li>
                                    <li><label class="dropdown-item d-flex align-items-center gap-2"><input class="form-check-input m-0" type="checkbox" data-col-toggle="attention" checked> Attention</label></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="table-responsive table-card mb-1">
                    <table class="table table-nowrap align-middle">
                        <thead class="text-muted table-light">
                            <tr class="text-uppercase">
                                <th data-col-key="booking_id">Booking ID</th>
                                <th data-col-key="guest_name">Guest Name</th>
                                <th data-col-key="tour_package">Tour Package</th>
                                <th data-col-key="departure_date">Departure Date</th>
                                <th data-col-key="location">Location</th>
                                <th data-col-key="guide">Guide</th>
                                <th data-col-key="pax">PAX</th>
                                @if(($canViewRevenue ?? false) === true)
                                    <th data-col-key="channel">Channel</th>
                                    <th data-col-key="net_revenue">Net Revenue</th>
                                @endif
                                <th data-col-key="status">Status</th>
                                <th data-col-key="assigned_guide">Assigned Guide</th>
                                <th data-col-key="attention">Attention</th>
                                <th>Action</th>
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
                                    $statusLabel = str($status)->replace('_', ' ')->title();
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
                                                Net IDR: IDR {{ number_format($idrNet, 0) }}
                                            </small>
                                        </td>
                                    @endif
                                    <td data-col-key="status">
                                        <span class="badge text-uppercase {{ $statusClass }}">{{ $statusLabel ?: 'Unknown' }}</span>
                                        @if(!empty($bookingAllocationReadinessWarnings[$booking->id] ?? null))
                                            <div class="mt-1">
                                                <span class="badge bg-warning-subtle text-warning text-wrap text-start"
                                                    title="{{ $bookingAllocationReadinessWarnings[$booking->id] }}">
                                                    <i class="ri-ship-line align-bottom me-1"></i>Resource
                                                </span>
                                            </div>
                                        @endif
                                    </td>
                                    <td data-col-key="assigned_guide">{{ $booking->assigned_to_name ?? '-' }}</td>
                                    <td data-col-key="attention">
                                        @if($booking->needs_attention)
                                            <span class="badge bg-danger-subtle text-danger"><i class="ri-alert-line align-bottom me-1"></i>Needs Follow-up</span>
                                        @else
                                            <span class="badge bg-success-subtle text-success"><i class="ri-checkbox-circle-line align-bottom me-1"></i>Normal</span>
                                        @endif
                                        @if(($booking->reschedules_count ?? 0) > 0)
                                            <div class="mt-1">
                                                <span class="badge bg-warning-subtle text-warning">
                                                    <i class="ri-calendar-event-line align-bottom me-1"></i>Reschedule x{{ $booking->reschedules_count }}
                                                </span>
                                            </div>
                                            @if($booking->latestReschedule)
                                                <small class="text-muted d-block mt-1">
                                                    Workflow:
                                                    <span class="fw-semibold text-capitalize">{{ str_replace('_', ' ', $booking->latestReschedule->workflow_status) }}</span>
                                                </small>
                                            @endif
                                            <small class="text-muted d-block mt-1">
                                                Last request:
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
                                            <i class="ri-whatsapp-line align-bottom me-1"></i>Reminder
                                        </button>
                                        <button
                                            type="button"
                                            class="btn btn-sm btn-soft-info js-open-reminder-history-modal mt-1"
                                            data-bs-toggle="modal"
                                            data-bs-target="#bookingReminderHistoryModal"
                                            data-booking-id="{{ $booking->id }}"
                                            data-booking-code="#BK{{ str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT) }}"
                                        >
                                            <i class="ri-history-line align-bottom me-1"></i>Riwayat
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
                                            <i class="ri-calendar-check-line align-bottom me-1"></i>Reschedule
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
                                                <i class="ri-archive-stack-line align-bottom me-1"></i>Resource
                                            </button>
                                        @endif
                                    </td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="{{ ($canViewRevenue ?? false) ? 13 : 11 }}" class="text-center py-4 text-muted">
                                        No booking data available.
                                    </td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
                <div class="alert alert-info mt-3 mb-0">
                    Booking data is loaded from database and synchronized with the Booking Calendar page.
                </div>
                @if ($bookings->count() > 0)
                    <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                        <small class="text-muted">
                            Menampilkan {{ $bookings->firstItem() }} - {{ $bookings->lastItem() }} dari
                            {{ $bookings->total() }} booking
                        </small>
                        {{ $bookings->links() }}
                    </div>
                @endif
                <div id="bookingTableEmptyState" class="noresult text-center py-4" style="display: none;">
                    <lord-icon src="https://cdn.lordicon.com/msoeawqm.json" trigger="loop" colors="primary:#405189,secondary:#0ab39c" style="width:75px;height:75px"></lord-icon>
                    <h5 class="mt-2">No Result Found</h5>
                    <p class="text-muted mb-0">Tidak ada booking dengan status yang dipilih.</p>
                </div>
                <div class="modal fade zoomIn" id="bookingReminderModal" tabindex="-1" aria-labelledby="bookingReminderModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <form method="POST" id="bookingReminderForm">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="bookingReminderModalLabel">Kirim Reminder WhatsApp</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted mb-3" id="bookingReminderTargetText">Pilih template reminder.</p>
                                    <div class="mb-0">
                                        <label class="form-label">Template WhatsApp</label>
                                        <select class="form-select" name="template_id" required>
                                            @foreach($reminderTemplates as $template)
                                                <option value="{{ $template->id }}" {{ (int) ($defaultReminderTemplateId ?? 0) === (int) $template->id ? 'selected' : '' }}>
                                                    {{ $template->name }}
                                                </option>
                                            @endforeach
                                        </select>
                                        @if($reminderTemplates->isEmpty())
                                            <small class="text-danger d-block mt-2">
                                                Belum ada template. Buat dulu di menu WhatsApp Template Message.
                                            </small>
                                        @endif
                                    </div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-success" {{ $reminderTemplates->isEmpty() ? 'disabled' : '' }}>
                                        <i class="ri-send-plane-line align-bottom me-1"></i>Buka WhatsApp
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade zoomIn" id="bookingReminderHistoryModal" tabindex="-1" aria-labelledby="bookingReminderHistoryModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered">
                        <div class="modal-content">
                            <div class="modal-header">
                                <h5 class="modal-title" id="bookingReminderHistoryModalLabel">Riwayat Reminder</h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted mb-3" id="bookingReminderHistoryTargetText">Riwayat pengiriman reminder.</p>
                                <div id="bookingReminderHistoryList" class="list-group list-group-flush"></div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">Tutup</button>
                            </div>
                        </div>
                    </div>
                </div>
                <div class="modal fade zoomIn" id="bookingRescheduleModal" tabindex="-1" aria-labelledby="bookingRescheduleModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <form method="POST" id="bookingRescheduleForm">
                                @csrf
                                <input type="hidden" name="reschedule_id" id="rescheduleIdInput">
                                <div class="modal-header">
                                    <h5 class="modal-title" id="bookingRescheduleModalLabel">Manage Reschedule</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted mb-2" id="bookingRescheduleTargetText">Pilih request reschedule untuk diproses.</p>
                                    <div class="alert alert-light border mb-3">
                                        <div class="small text-muted">Current departure</div>
                                        <div class="fw-semibold" id="bookingRescheduleCurrentDate">-</div>
                                    </div>
                                    <div class="row g-3">
                                        <div class="col-md-4">
                                            <label class="form-label">Workflow status</label>
                                            <select class="form-select" name="workflow_status" id="rescheduleWorkflowStatus" required>
                                                <option value="reviewed">Reviewed</option>
                                                <option value="approved">Approved</option>
                                                <option value="rejected">Rejected</option>
                                                <option value="completed">Completed</option>
                                            </select>
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Requested new date</label>
                                            <input type="datetime-local" class="form-control" name="requested_tour_start_at" id="rescheduleRequestedDate">
                                        </div>
                                        <div class="col-md-4">
                                            <label class="form-label">Final approved date</label>
                                            <input type="datetime-local" class="form-control" name="final_tour_start_at" id="rescheduleFinalDate">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Reason (optional)</label>
                                            <input type="text" class="form-control" name="requested_reason" id="rescheduleReason" maxlength="255" placeholder="Weather, guest request, operations capacity, etc.">
                                        </div>
                                        <div class="col-md-6">
                                            <label class="form-label">Notes</label>
                                            <textarea class="form-control" rows="2" name="notes" id="rescheduleNotes" maxlength="1000" placeholder="Internal notes for ops follow-up"></textarea>
                                        </div>
                                    </div>
                                    <hr class="my-4">
                                    <h6 class="mb-2">Reschedule timeline</h6>
                                    <div id="bookingRescheduleHistoryList" class="list-group list-group-flush"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-warning" id="saveRescheduleWorkflowBtn">
                                        <i class="ri-save-line align-bottom me-1"></i>Simpan Workflow
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
                <div class="modal fade zoomIn" id="bookingResourceAllocationModal" tabindex="-1" aria-labelledby="bookingResourceAllocationModalLabel" aria-hidden="true">
                    <div class="modal-dialog modal-dialog-centered modal-lg">
                        <div class="modal-content">
                            <form method="POST" id="bookingResourceAllocationForm">
                                @csrf
                                <div class="modal-header">
                                    <h5 class="modal-title" id="bookingResourceAllocationModalLabel">Resource Allocation</h5>
                                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                </div>
                                <div class="modal-body">
                                    <p class="text-muted mb-3" id="bookingResourceAllocationTargetText">Atur resource untuk booking ini.</p>
                                    <div class="row g-3">
                                        <div class="col-md-6">
                                            <label class="form-label">Resource</label>
                                            <select class="form-select" name="tenant_resource_id" id="allocationResourceId" required>
                                                <option value="">Pilih resource...</option>
                                                @php
                                                    $resourceGroups = ($resourceOptions ?? collect())->groupBy('tenant_id');
                                                    $showResourceTenantOptgroups = auth()->user()?->isSuperAdmin() ?? false;
                                                @endphp
                                                @foreach($resourceGroups as $resourceTenantId => $resourcesInTenant)
                                                    @if($showResourceTenantOptgroups)
                                                        <optgroup label="{{ optional($resourcesInTenant->first()->tenant)->name ?? 'Tenant #'.$resourceTenantId }}">
                                                            @foreach($resourcesInTenant as $resourceOption)
                                                                <option
                                                                    value="{{ $resourceOption->id }}"
                                                                    data-tenant-id="{{ $resourceOption->tenant_id }}"
                                                                >
                                                                    {{ $resourceOption->name }} ({{ strtoupper((string) $resourceOption->resource_type) }}){{ $resourceOption->reference_code ? ' - '.$resourceOption->reference_code : '' }}
                                                                </option>
                                                            @endforeach
                                                        </optgroup>
                                                    @else
                                                        @foreach($resourcesInTenant as $resourceOption)
                                                            <option
                                                                value="{{ $resourceOption->id }}"
                                                                data-tenant-id="{{ $resourceOption->tenant_id }}"
                                                            >
                                                                {{ $resourceOption->name }} ({{ strtoupper((string) $resourceOption->resource_type) }}){{ $resourceOption->reference_code ? ' - '.$resourceOption->reference_code : '' }}
                                                            </option>
                                                        @endforeach
                                                    @endif
                                                @endforeach
                                            </select>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Tanggal alokasi</label>
                                            <input type="date" class="form-control" name="allocation_date" id="allocationDate" required>
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Allocated pax</label>
                                            <input type="number" class="form-control" name="allocated_pax" id="allocationPax" min="1" max="100000">
                                        </div>
                                        <div class="col-md-3">
                                            <label class="form-label">Allocated units</label>
                                            <input type="number" class="form-control" name="allocated_units" id="allocationUnits" min="1" max="100000">
                                        </div>
                                        <div class="col-md-9">
                                            <label class="form-label">Notes</label>
                                            <input type="text" class="form-control" name="notes" id="allocationNotes" maxlength="500">
                                        </div>
                                    </div>
                                    <hr class="my-4">
                                    <h6 class="mb-2">Existing allocations</h6>
                                    <div id="bookingResourceAllocationHistoryList" class="list-group list-group-flush"></div>
                                </div>
                                <div class="modal-footer">
                                    <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                                    <button type="submit" class="btn btn-primary">
                                        <i class="ri-save-line align-bottom me-1"></i>Simpan Alokasi
                                    </button>
                                </div>
                            </form>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
<script>
    document.addEventListener('DOMContentLoaded', function () {
        var tabContainer = document.getElementById('bookingStatusTabs');
        if (!tabContainer) {
            return;
        }

        var tabLinks = tabContainer.querySelectorAll('[data-status-filter]');
        var rows = document.querySelectorAll('[data-booking-row]');
        var emptyState = document.getElementById('bookingTableEmptyState');
        var keywordInput = document.getElementById('bookingKeywordFilter');
        var dateRangeInput = document.getElementById('bookingDateRangeFilter');
        var workflowChipButtons = document.querySelectorAll('[data-workflow-chip]');
        var resetButton = document.getElementById('resetBookingFilters');
        var reminderHistoryModalEl = document.getElementById('bookingReminderHistoryModal');
        var reminderModalEl = document.getElementById('bookingReminderModal');
        var rescheduleModalEl = document.getElementById('bookingRescheduleModal');
        var resourceAllocationModalEl = document.getElementById('bookingResourceAllocationModal');
        var reminderForm = document.getElementById('bookingReminderForm');
        var rescheduleForm = document.getElementById('bookingRescheduleForm');
        var resourceAllocationForm = document.getElementById('bookingResourceAllocationForm');
        var reminderTargetText = document.getElementById('bookingReminderTargetText');
        var rescheduleTargetText = document.getElementById('bookingRescheduleTargetText');
        var resourceAllocationTargetText = document.getElementById('bookingResourceAllocationTargetText');
        var reminderHistoryTargetText = document.getElementById('bookingReminderHistoryTargetText');
        var reminderHistoryList = document.getElementById('bookingReminderHistoryList');
        var rescheduleHistoryList = document.getElementById('bookingRescheduleHistoryList');
        var resourceAllocationHistoryList = document.getElementById('bookingResourceAllocationHistoryList');
        var rescheduleIdInput = document.getElementById('rescheduleIdInput');
        var rescheduleCurrentDate = document.getElementById('bookingRescheduleCurrentDate');
        var rescheduleStatusInput = document.getElementById('rescheduleWorkflowStatus');
        var rescheduleRequestedDateInput = document.getElementById('rescheduleRequestedDate');
        var rescheduleFinalDateInput = document.getElementById('rescheduleFinalDate');
        var rescheduleReasonInput = document.getElementById('rescheduleReason');
        var rescheduleNotesInput = document.getElementById('rescheduleNotes');
        var saveRescheduleWorkflowBtn = document.getElementById('saveRescheduleWorkflowBtn');
        var allocationResourceId = document.getElementById('allocationResourceId');
        var allocationDateInput = document.getElementById('allocationDate');
        var reminderHistoryByBooking = @json($reminderHistoryByBooking ?? []);
        var rescheduleHistoryByBooking = @json($rescheduleHistoryByBooking ?? []);
        var bookingAllocationsByBooking = @json($bookingAllocationsByBooking ?? []);
        var reminderTemplateSelect = reminderForm ? reminderForm.querySelector('select[name="template_id"]') : null;
        var defaultReminderTemplateId = '{{ (string) ($defaultReminderTemplateId ?? '') }}';
        var csrfToken = '{{ csrf_token() }}';
        var activeStatus = 'all';
        var columnToggleMenu = document.getElementById('bookingColumnToggleMenu');
        var columnStorageKey = 'bookingTableColumnVisibility.v1';
        function applyColumnVisibilityMap(visibilityMap) {
            if (!visibilityMap || typeof visibilityMap !== 'object') {
                return;
            }
            Object.keys(visibilityMap).forEach(function (key) {
                var isVisible = visibilityMap[key] !== false;
                var cells = document.querySelectorAll('[data-col-key="' + key + '"]');
                cells.forEach(function (cell) {
                    cell.style.display = isVisible ? '' : 'none';
                });
                if (columnToggleMenu) {
                    var checkbox = columnToggleMenu.querySelector('input[data-col-toggle="' + key + '"]');
                    if (checkbox) {
                        checkbox.checked = isVisible;
                    }
                }
            });
        }

        function collectColumnVisibilityMap() {
            var visibilityMap = {};
            if (!columnToggleMenu) {
                return visibilityMap;
            }
            columnToggleMenu.querySelectorAll('input[data-col-toggle]').forEach(function (checkbox) {
                var key = checkbox.getAttribute('data-col-toggle');
                if (!key) {
                    return;
                }
                visibilityMap[key] = checkbox.checked;
            });
            return visibilityMap;
        }

        function saveColumnVisibilityMap() {
            try {
                localStorage.setItem(columnStorageKey, JSON.stringify(collectColumnVisibilityMap()));
            } catch (error) {}
        }

        function initColumnToggles() {
            if (!columnToggleMenu) {
                return;
            }
            var defaultMap = collectColumnVisibilityMap();
            var savedMap = null;
            try {
                savedMap = JSON.parse(localStorage.getItem(columnStorageKey) || 'null');
            } catch (error) {
                savedMap = null;
            }
            applyColumnVisibilityMap(savedMap && typeof savedMap === 'object' ? savedMap : defaultMap);

            columnToggleMenu.querySelectorAll('input[data-col-toggle]').forEach(function (checkbox) {
                checkbox.addEventListener('change', function () {
                    var key = checkbox.getAttribute('data-col-toggle');
                    if (!key) {
                        return;
                    }
                    applyColumnVisibilityMap({ [key]: checkbox.checked });
                    saveColumnVisibilityMap();
                });
            });
        }

        function setActiveWorkflowChip(targetValue) {
            workflowChipButtons.forEach(function (button) {
                var chipValue = button.getAttribute('data-workflow-chip') || '';
                button.classList.toggle('active', chipValue === targetValue);
            });
        }

        function rowMatchesActiveStatusContext(row) {
            var rowStatus = row.getAttribute('data-status') || '';
            var rowResponse = row.getAttribute('data-response') || '';
            var statusMatch = activeStatus === 'all' || rowStatus === activeStatus;
            if (activeStatus === 'reschedule_requested') {
                statusMatch = rowResponse === 'reschedule_requested';
            }

            return statusMatch;
        }

        function updateWorkflowChipCounters() {
            var counters = {
                all: 0,
                requested: 0,
                reviewed: 0,
                approved: 0,
                rejected: 0,
                completed: 0,
                no_request: 0,
            };

            rows.forEach(function (row) {
                if (!rowMatchesActiveStatusContext(row)) {
                    return;
                }

                counters.all += 1;
                var rowWorkflow = (row.getAttribute('data-reschedule-workflow') || '').toLowerCase();
                if (rowWorkflow === '') {
                    counters.no_request += 1;
                    return;
                }

                if (Object.prototype.hasOwnProperty.call(counters, rowWorkflow)) {
                    counters[rowWorkflow] += 1;
                }
            });

            workflowChipButtons.forEach(function (button) {
                var key = button.getAttribute('data-workflow-chip') || '';
                var countEl = button.querySelector('[data-workflow-chip-count]');
                if (!countEl || !Object.prototype.hasOwnProperty.call(counters, key)) {
                    return;
                }
                countEl.textContent = String(counters[key]);
            });
        }

        function formatDateTime(value) {
            if (!value) {
                return '-';
            }
            try {
                return new Date(value).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' });
            } catch (error) {
                return value;
            }
        }

        function toDateTimeLocal(value) {
            if (!value) {
                return '';
            }
            try {
                var date = new Date(value);
                var offset = date.getTimezoneOffset();
                date = new Date(date.getTime() - (offset * 60000));
                return date.toISOString().slice(0, 16);
            } catch (error) {
                return '';
            }
        }

        function parseDateRange(value) {
            if (!value || value.indexOf(' to ') === -1 || typeof flatpickr === 'undefined') {
                return { start: null, end: null };
            }

            var parts = value.split(' to ');
            var startDate = flatpickr.parseDate(parts[0], 'd M, Y');
            var endDate = flatpickr.parseDate(parts[1], 'd M, Y');

            if (!startDate || !endDate) {
                return { start: null, end: null };
            }

            return {
                start: flatpickr.formatDate(startDate, 'Y-m-d'),
                end: flatpickr.formatDate(endDate, 'Y-m-d'),
            };
        }

        function applyFilter() {
            var keyword = (keywordInput ? keywordInput.value : '').toLowerCase().trim();
            var dateRange = parseDateRange(dateRangeInput ? dateRangeInput.value : '');
            var activeWorkflowChipEl = document.querySelector('[data-workflow-chip].active');
            var workflowFilter = ((activeWorkflowChipEl && activeWorkflowChipEl.getAttribute('data-workflow-chip')) || 'all').toLowerCase();
            var visibleRows = 0;

            rows.forEach(function (row) {
                var rowWorkflow = (row.getAttribute('data-reschedule-workflow') || '').toLowerCase();
                var rowSearch = row.getAttribute('data-search') || '';
                var rowDate = row.getAttribute('data-start-date') || '';
                var statusMatch = rowMatchesActiveStatusContext(row);
                var keywordMatch = !keyword || rowSearch.indexOf(keyword) !== -1;
                var dateMatch = true;
                var workflowMatch = workflowFilter === 'all' ||
                    (workflowFilter === 'no_request' ? rowWorkflow === '' : rowWorkflow === workflowFilter);

                if (dateRange.start && dateRange.end) {
                    dateMatch = rowDate >= dateRange.start && rowDate <= dateRange.end;
                }

                var visible = statusMatch && keywordMatch && dateMatch && workflowMatch;
                row.style.display = visible ? '' : 'none';
                if (visible) {
                    visibleRows += 1;
                }
            });

            if (emptyState) {
                emptyState.style.display = visibleRows === 0 ? '' : 'none';
            }

            updateWorkflowChipCounters();
        }

        if (keywordInput) {
            keywordInput.addEventListener('input', function () {
                applyFilter();
            });
        }

        if (dateRangeInput) {
            dateRangeInput.addEventListener('change', function () {
                applyFilter();
            });
        }

        workflowChipButtons.forEach(function (button) {
            button.addEventListener('click', function () {
                var targetWorkflow = button.getAttribute('data-workflow-chip') || 'all';
                setActiveWorkflowChip(targetWorkflow);
                applyFilter();
            });
        });

        if (resetButton) {
            resetButton.addEventListener('click', function () {
                if (keywordInput) {
                    keywordInput.value = '';
                }
                if (dateRangeInput) {
                    dateRangeInput.value = '';
                    if (dateRangeInput._flatpickr) {
                        dateRangeInput._flatpickr.clear();
                    }
                }
                setActiveWorkflowChip('all');

                activeStatus = 'all';
                tabLinks.forEach(function (item) {
                    item.classList.remove('active');
                });

                var firstTab = tabContainer.querySelector('[data-status-filter="all"]');
                if (firstTab) {
                    firstTab.classList.add('active');
                }

                applyFilter();
            });
        }

        tabLinks.forEach(function (link) {
            link.addEventListener('click', function (event) {
                event.preventDefault();

                tabLinks.forEach(function (item) {
                    item.classList.remove('active');
                });
                link.classList.add('active');

                activeStatus = link.getAttribute('data-status-filter') || 'all';
                applyFilter();
            });
        });

        if (reminderModalEl) {
            reminderModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !reminderForm) {
                    return;
                }
                var bookingId = trigger.getAttribute('data-booking-id');
                var bookingName = trigger.getAttribute('data-booking-name') || '-';
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                reminderForm.action = '/apps-bookings/' + bookingId + '/send-reminder';
                if (reminderTargetText) {
                    reminderTargetText.textContent = 'Booking ' + bookingCode + ' - ' + bookingName;
                }
                if (reminderTemplateSelect && defaultReminderTemplateId) {
                    reminderTemplateSelect.value = defaultReminderTemplateId;
                }
            });
        }

        if (reminderHistoryModalEl) {
            reminderHistoryModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !reminderHistoryList) {
                    return;
                }
                var bookingId = String(trigger.getAttribute('data-booking-id') || '');
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                var historyItems = reminderHistoryByBooking[bookingId] || [];
                if (reminderHistoryTargetText) {
                    reminderHistoryTargetText.textContent = 'Detail reminder untuk booking ' + bookingCode;
                }
                if (historyItems.length === 0) {
                    reminderHistoryList.innerHTML = '<div class="text-muted py-2">Belum ada reminder yang dikirim untuk booking ini.</div>';
                } else {
                    reminderHistoryList.innerHTML = historyItems.map(function (item) {
                        var sentAt = item.sent_at ? new Date(item.sent_at).toLocaleString('id-ID', { dateStyle: 'medium', timeStyle: 'short' }) : '-';
                        return '' +
                            '<div class="list-group-item px-0">' +
                                '<div class="fw-semibold">' + sentAt + '</div>' +
                                '<div class="text-muted small">Template: ' + (item.template_name || '-') + '</div>' +
                                '<div class="text-muted small">Tujuan: ' + (item.sent_to_phone || '-') + '</div>' +
                            '</div>';
                    }).join('');
                }
            });
        }

        if (rescheduleModalEl) {
            rescheduleModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !rescheduleForm || !rescheduleHistoryList) {
                    return;
                }

                var bookingId = String(trigger.getAttribute('data-booking-id') || '');
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                var bookingName = trigger.getAttribute('data-booking-name') || '-';
                var bookingStart = trigger.getAttribute('data-booking-start') || '';
                var historyItems = rescheduleHistoryByBooking[bookingId] || [];
                var latest = historyItems.length > 0 ? historyItems[0] : null;

                rescheduleForm.action = '/apps-bookings/' + bookingId + '/reschedule-workflow';
                if (rescheduleTargetText) {
                    rescheduleTargetText.textContent = 'Kelola reschedule untuk booking ' + bookingCode + ' - ' + bookingName;
                }
                if (rescheduleCurrentDate) {
                    rescheduleCurrentDate.textContent = formatDateTime(bookingStart);
                }

                if (rescheduleIdInput) {
                    rescheduleIdInput.value = latest ? String(latest.id || '') : '';
                }
                if (rescheduleStatusInput) {
                    rescheduleStatusInput.value = latest ? (latest.workflow_status || 'reviewed') : 'reviewed';
                }
                if (rescheduleRequestedDateInput) {
                    rescheduleRequestedDateInput.value = latest ? toDateTimeLocal(latest.requested_tour_start_at) : '';
                }
                if (rescheduleFinalDateInput) {
                    rescheduleFinalDateInput.value = latest ? toDateTimeLocal(latest.final_tour_start_at) : '';
                }
                if (rescheduleReasonInput) {
                    rescheduleReasonInput.value = latest ? (latest.requested_reason || '') : '';
                }
                if (rescheduleNotesInput) {
                    rescheduleNotesInput.value = latest ? (latest.notes || '') : '';
                }

                if (historyItems.length === 0) {
                    rescheduleHistoryList.innerHTML = '<div class="text-muted py-2">Belum ada request reschedule untuk booking ini.</div>';
                    if (saveRescheduleWorkflowBtn) {
                        saveRescheduleWorkflowBtn.disabled = true;
                    }
                    return;
                }

                if (saveRescheduleWorkflowBtn) {
                    saveRescheduleWorkflowBtn.disabled = false;
                }

                rescheduleHistoryList.innerHTML = historyItems.map(function (item) {
                    return '' +
                        '<div class="list-group-item px-0">' +
                            '<div class="d-flex justify-content-between flex-wrap gap-1">' +
                                '<div class="fw-semibold text-capitalize">' + (item.workflow_status || '-').replace('_', ' ') + '</div>' +
                                '<div class="small text-muted">' + formatDateTime(item.created_at) + '</div>' +
                            '</div>' +
                            '<div class="small text-muted">By: ' + (item.requested_by || '-') + ' | Source: ' + (item.request_source || '-') + '</div>' +
                            '<div class="small text-muted">Old: ' + formatDateTime(item.old_tour_start_at) + '</div>' +
                            '<div class="small text-muted">Requested: ' + formatDateTime(item.requested_tour_start_at) + '</div>' +
                            '<div class="small text-muted">Final: ' + formatDateTime(item.final_tour_start_at) + '</div>' +
                            '<div class="small text-muted">Reason: ' + (item.requested_reason || '-') + '</div>' +
                            '<div class="small text-muted">Notes: ' + (item.notes || '-') + '</div>' +
                        '</div>';
                }).join('');
            });
        }

        if (resourceAllocationModalEl) {
            resourceAllocationModalEl.addEventListener('show.bs.modal', function (event) {
                var trigger = event.relatedTarget;
                if (!trigger || !resourceAllocationForm || !resourceAllocationHistoryList) {
                    return;
                }

                var bookingId = String(trigger.getAttribute('data-booking-id') || '');
                var bookingCode = trigger.getAttribute('data-booking-code') || '';
                var bookingName = trigger.getAttribute('data-booking-name') || '-';
                var bookingDate = trigger.getAttribute('data-booking-date') || '';
                var allocations = bookingAllocationsByBooking[bookingId] || [];

                resourceAllocationForm.action = '/apps-bookings/' + bookingId + '/resource-allocations';
                if (resourceAllocationTargetText) {
                    resourceAllocationTargetText.textContent = 'Kelola alokasi resource untuk booking ' + bookingCode + ' - ' + bookingName;
                }
                if (allocationDateInput) {
                    allocationDateInput.value = bookingDate || '';
                }

                var activeRow = document.querySelector('[data-booking-row] button[data-booking-id="' + bookingId + '"]');
                var activeRowElement = activeRow ? activeRow.closest('[data-booking-row]') : null;
                var bookingTenantId = activeRowElement ? activeRowElement.getAttribute('data-tenant-id') : null;
                if (allocationResourceId) {
                    allocationResourceId.value = '';
                    allocationResourceId.querySelectorAll('option[data-tenant-id]').forEach(function (option) {
                        var canShow = !bookingTenantId || option.getAttribute('data-tenant-id') === bookingTenantId;
                        option.hidden = !canShow;
                    });
                }

                if (allocations.length === 0) {
                    resourceAllocationHistoryList.innerHTML = '<div class="text-muted py-2">Belum ada alokasi resource untuk booking ini.</div>';
                } else {
                    resourceAllocationHistoryList.innerHTML = allocations.map(function (item) {
                        return '' +
                            '<div class="list-group-item px-0 d-flex justify-content-between align-items-start gap-3">' +
                                '<div>' +
                                    '<div class="fw-semibold">' + (item.resource_name || '-') + '</div>' +
                                    '<div class="small text-muted">Date: ' + (item.allocation_date || '-') + '</div>' +
                                    '<div class="small text-muted">Pax: ' + (item.allocated_pax || '-') + ' | Units: ' + (item.allocated_units || '-') + '</div>' +
                                    '<div class="small text-muted">Notes: ' + (item.notes || '-') + '</div>' +
                                '</div>' +
                                '<form method="POST" action="/apps-bookings/' + bookingId + '/resource-allocations/' + item.id + '/delete">' +
                                    '<input type="hidden" name="_token" value="' + csrfToken + '">' +
                                    '<button type="submit" class="btn btn-sm btn-soft-danger">Unassign</button>' +
                                '</form>' +
                            '</div>';
                    }).join('');
                }
            });
        }

        applyFilter();
        setActiveWorkflowChip('all');
        initColumnToggles();
    });
</script>
@endsection
