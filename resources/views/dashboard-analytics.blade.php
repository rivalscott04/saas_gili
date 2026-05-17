@extends('layouts.master')
@section('title')
    @lang('translation.analytics')
@endsection
@section('css')
    <link href="{{ URL::asset('build/libs/jsvectormap/jsvectormap.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.dashboards') }}
        @endslot
        @slot('title')
            {{ __('translation.analytics') }}
        @endslot
    @endcomponent

    @php
        // Widget mini Setup Checklist — hanya tampil untuk tenant_admin yang belum
        // selesai setup (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §6.1 + Phase B).
        $dashboardOnboardingViewer = auth()->user();
        $showDashboardOnboardingWidget = false;
        $dashboardOnboardingDone = 0;
        $dashboardOnboardingTotal = 0;
        if ($dashboardOnboardingViewer !== null
            && $dashboardOnboardingViewer->isTenantAdmin()
            && $dashboardOnboardingViewer->tenant !== null) {
            $dashboardOnboardingService = app(\App\Services\OnboardingService::class);
            $dashboardOnboardingState = $dashboardOnboardingViewer->tenant->onboardingState;
            $dashboardOnboardingDone = $dashboardOnboardingService
                ->mandatoryCompleted($dashboardOnboardingViewer->tenant);
            $dashboardOnboardingTotal = $dashboardOnboardingService->mandatoryTotal();
            $showDashboardOnboardingWidget = $dashboardOnboardingState?->dismissed_at === null
                && $dashboardOnboardingDone < $dashboardOnboardingTotal;
        }
    @endphp

    @if ($showDashboardOnboardingWidget)
        <div class="row" data-onboarding="dashboard-widget">
            <div class="col-12">
                <div class="card border border-primary-subtle">
                    <div class="card-body d-flex flex-wrap gap-3 align-items-center">
                        <div class="flex-shrink-0">
                            <div class="avatar-sm">
                                <div class="avatar-title rounded-circle bg-primary-subtle text-primary fs-4">
                                    <i class="bx bx-rocket"></i>
                                </div>
                            </div>
                        </div>
                        <div class="flex-grow-1">
                            <h5 class="mb-1">{{ __('translation.onboarding-widget-title') }}</h5>
                            <p class="text-muted mb-2">{{ __('translation.onboarding-widget-help') }}</p>
                            @php
                                $widgetPct = $dashboardOnboardingTotal > 0
                                    ? (int) round(($dashboardOnboardingDone / $dashboardOnboardingTotal) * 100)
                                    : 0;
                            @endphp
                            <div class="d-flex align-items-center gap-2">
                                <div class="progress flex-grow-1" style="height: 6px;" role="progressbar"
                                    aria-valuenow="{{ $widgetPct }}" aria-valuemin="0" aria-valuemax="100">
                                    <div class="progress-bar bg-primary" style="width: {{ $widgetPct }}%"></div>
                                </div>
                                <small class="text-muted">{{ __('translation.onboarding-progress', ['done' => $dashboardOnboardingDone, 'total' => $dashboardOnboardingTotal]) }}</small>
                            </div>
                        </div>
                        <div class="flex-shrink-0">
                            <a href="{{ route('onboarding.index') }}" class="btn btn-primary">
                                {{ __('translation.onboarding-widget-cta') }}
                                <i class="bx bx-right-arrow-alt ms-1"></i>
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body d-flex flex-wrap gap-3 align-items-end">
                    @if(($isSuperAdminViewer ?? false) === true)
                        <form method="GET" action="{{ url('/dashboard-analytics') }}" class="d-flex flex-wrap gap-2 align-items-end">
                            <div>
                                <label for="tenantScope" class="form-label mb-1">{{ __('translation.tenant-scope') }}</label>
                                <select id="tenantScope" name="tenant" class="form-select">
                                    <option value="">{{ __('translation.all-tenants') }}</option>
                                    @foreach(($tenantOptions ?? collect()) as $tenant)
                                        <option value="{{ $tenant->code }}" {{ ($selectedTenantCode ?? '') === (string) $tenant->code ? 'selected' : '' }}>
                                            {{ $tenant->name }}
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                            <div>
                                <button class="btn btn-primary" type="submit">{{ __('translation.apply') }}</button>
                            </div>
                        </form>
                    @else
                        <div>
                            <div class="text-muted small">{{ __('translation.data-scope') }}</div>
                            <div class="fw-semibold">{{ __('translation.tenant-only-access') }}</div>
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.total-bookings') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($summary['total_bookings'] ?? 0) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.upcoming-tours') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($summary['upcoming_tours'] ?? 0) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.guests-expected') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($summary['guests_expected'] ?? 0) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.needs-attention-24h') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($summary['needs_attention'] ?? 0) }}</h2>
                </div>
            </div>
        </div>
    </div>

    @if(($canViewRevenue ?? false) === true)
    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.gross-sales') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format((float) ($summary['gross_sales'] ?? 0), 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.net-revenue') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format((float) ($summary['net_revenue'] ?? 0), 2) }}</h2>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="fw-medium text-muted mb-0">{{ __('translation.revenue-idr') }}</p>
                    <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format((float) ($summary['revenue_idr'] ?? 0), 2) }}</h2>
                </div>
            </div>
        </div>
    </div>
    @endif

    <div class="row">
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('translation.urgent-bookings') }}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('translation.tour') }}</th>
                                    @if(($isSuperAdminViewer ?? false) === true)
                                        <th>Tenant</th>
                                    @endif
                                    <th>{{ __('translation.customer') }}</th>
                                    <th>{{ __('translation.start') }}</th>
                                    <th>{{ __('translation.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($urgentBookings ?? collect()) as $booking)
                                    <tr>
                                        <td>{{ $booking->tour_name }}</td>
                                        @if(($isSuperAdminViewer ?? false) === true)
                                            <td>{{ $booking->tenant?->name ?? '-' }}</td>
                                        @endif
                                        <td>{{ $booking->customer?->name ?? $booking->customer_name }}</td>
                                        <td>{{ optional($booking->tour_start_at)->format('d M Y H:i') }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ $booking->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($isSuperAdminViewer ?? false) ? 5 : 4 }}" class="text-muted">{{ __('translation.no-urgent-bookings-24h') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
        <div class="col-xl-6">
            <div class="card">
                <div class="card-header align-items-center d-flex">
                    <h4 class="card-title mb-0 flex-grow-1">{{ __('translation.recent-bookings') }}</h4>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light">
                                <tr>
                                    <th>{{ __('translation.tour') }}</th>
                                    @if(($isSuperAdminViewer ?? false) === true)
                                        <th>Tenant</th>
                                    @endif
                                    <th>{{ __('translation.customer') }}</th>
                                    <th>PAX</th>
                                    <th>{{ __('translation.status') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse (($recentBookings ?? collect()) as $booking)
                                    <tr>
                                        <td>{{ $booking->tour_name }}</td>
                                        @if(($isSuperAdminViewer ?? false) === true)
                                            <td>{{ $booking->tenant?->name ?? '-' }}</td>
                                        @endif
                                        <td>{{ $booking->customer?->name ?? $booking->customer_name }}</td>
                                        <td>{{ $booking->participants }}</td>
                                        <td><span class="badge bg-primary-subtle text-primary">{{ $booking->status }}</span></td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="{{ ($isSuperAdminViewer ?? false) ? 5 : 4 }}" class="text-muted">{{ __('translation.no-recent-bookings') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- Widget platform & OTA khusus superadmin (bukan demo Velzon). --}}
    @if (($isSuperAdminViewer ?? false) === true)
        @include('partials.dashboard.superadmin-ota-widgets')
    @endif

    @if (! ($isSuperAdminViewer ?? false))
        <div class="row">
            <div class="col-12">
                <div class="card border-0 bg-light-subtle">
                    <div class="card-body d-flex flex-wrap align-items-center justify-content-between gap-3">
                        <div>
                            <h5 class="mb-1">{{ __('translation.tenant-dashboard-help-title') }}</h5>
                            <p class="text-muted mb-0">{{ __('translation.tenant-dashboard-help-text') }}</p>
                        </div>
                        <div class="d-flex flex-wrap gap-2">
                            <a href="{{ url('apps-bookings') }}" class="btn btn-primary btn-sm">
                                <i class="ri-list-check align-bottom me-1"></i>{{ __('translation.bookings') }}
                            </a>
                            <a href="{{ url('apps-bookings-calendar') }}" class="btn btn-soft-primary btn-sm">
                                <i class="ri-calendar-event-line align-bottom me-1"></i>{{ __('translation.booking-calendar') }}
                            </a>
                            <a href="{{ route('bookings.recap') }}" class="btn btn-soft-info btn-sm">
                                <i class="ri-bar-chart-2-line align-bottom me-1"></i>{{ __('translation.sales-report-booking-recap') }}
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    @endif
@endsection
@section('script')
    <!-- apexcharts -->
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/jsvectormap/jsvectormap.min.js') }}"></script>
    <script src="{{ URL::asset('build/libs/jsvectormap/maps/world-merc.js') }}"></script>

    <!-- dashboard init -->
    <script src="{{ URL::asset('build/js/pages/dashboard-analytics.init.js') }}"></script>
@endsection
