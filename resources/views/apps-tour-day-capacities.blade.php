@extends('layouts.master')

@section('title')
    {{ __('translation.tour-daily-capacity') }}
@endsection

@section('css')
    {{-- Shepherd.js style untuk onboarding tour (docs/ux-review/2026-05-14-tenant-onboarding-plan.md Phase E). --}}
    <link href="{{ URL::asset('build/libs/shepherd.js/css/shepherd.css') }}" rel="stylesheet" type="text/css" />
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.operations-resources') }}
        @endslot
        @slot('title')
            {{ __('translation.set-daily-quota-per-date') }}
        @endslot
    @endcomponent

    <div class="row" id="tourDayCapacityAjaxContainer">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.choose-tour') }}</h5>
                </div>
                <div class="card-body">
                    @if ($showTenantSwitcher)
                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.tenant') }}</label>
                            <select class="form-select" id="capacityTenantSwitcher">
                                @foreach ($availableTenants as $tenantOption)
                                    <option value="{{ $tenantOption->code }}"
                                        {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                        {{ $tenantOption->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <form method="GET" action="{{ route('tour-day-capacities.index') }}" id="tourPickForm">
                        @if ($showTenantSwitcher)
                            <input type="hidden" name="tenant" value="{{ $tenant->code }}">
                        @endif
                        <label class="form-label">{{ __('translation.main-tour') }}</label>
                        <select class="form-select mb-3" name="tour_id" id="tourCapacityPicker" required data-onboarding="tour-picker">
                            <option value="">{{ __('translation.select-placeholder') }}</option>
                            @foreach ($tours as $t)
                                <option value="{{ $t->id }}"
                                    {{ $selectedTour && (int) $selectedTour->id === (int) $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <p class="text-muted small mb-0">
                        {{ __('translation.tour-daily-capacity-help') }}
                    </p>
                </div>
            </div>
            @if ($selectedTour)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">{{ __('translation.add-update-special-date-quota') }}</h5>
                    </div>
                    <div class="card-body" data-onboarding="capacity-form">
                        <form method="POST" action="{{ route('tour-day-capacities.store') }}">
                            @csrf
                            @if ($showTenantSwitcher)
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                            @endif
                            <input type="hidden" name="tour_id" value="{{ $selectedTour->id }}">
                            <div class="mb-3">
                                <label class="form-label">{{ __('translation.service-date') }}</label>
                                <input type="date" class="form-control" name="service_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">{{ __('translation.max-participants') }}</label>
                                <input type="number" class="form-control" name="max_pax" min="1" max="100000" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100" data-onboarding="capacity-save">{{ __('translation.save-this-date-quota') }}</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.special-date-quota-list') }}</h5>
                    @if ($selectedTour)
                        <span class="badge bg-primary-subtle text-primary">{{ $selectedTour->name }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if (! $selectedTour)
                        {{-- Empty state ramah onboarding (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §6.1).
                             Tetap menyertakan deeplink ke Tour Management supaya tenant bisa atur default kuota di sana. --}}
                        <x-onboarding.empty-state
                            icon="bx-calendar-event"
                            :title="__('translation.empty-state-pick-tour-title')"
                            :description="__('translation.empty-state-pick-tour-desc')"
                            :actions="[
                                ['label' => __('translation.empty-state-pick-tour-cta'), 'href' => route('tours.index'), 'variant' => 'secondary'],
                            ]"
                        />
                    @elseif ($capacities->isEmpty())
                        <div class="alert alert-light border mb-0">{{ __('translation.no-special-date-quota-yet') }}</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>{{ __('translation.date') }}</th>
                                        <th>{{ __('translation.max-participants') }}</th>
                                        <th class="text-end">{{ __('translation.action') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($capacities as $row)
                                        <tr>
                                            <td>{{ $row->service_date?->format('Y-m-d') }}</td>
                                            <td>{{ $row->max_pax }}</td>
                                            <td class="text-end">
                                                <form method="POST"
                                                    action="{{ route('tour-day-capacities.destroy', $row) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm(@js(__('translation.confirm-delete-special-date-quota')));">
                                                    @csrf
                                                    @if ($showTenantSwitcher)
                                                        <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                                    @endif
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">{{ __('translation.delete') }}</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                {{ __('translation.showing-range-of-total-capacity-overrides', ['from' => $capacities->firstItem(), 'to' => $capacities->lastItem(), 'total' => $capacities->total()]) }}
                            </small>
                            {{ $capacities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var initCapacityAjax = function() {
                var switcher = document.getElementById('capacityTenantSwitcher');
                var picker = document.getElementById('tourCapacityPicker');
                var ajaxContainer = document.getElementById('tourDayCapacityAjaxContainer');
                var baseUrl = "{{ route('tour-day-capacities.index') }}";

                var buildUrl = function(tenantCode, tourId) {
                    var params = new URLSearchParams();
                    if (tenantCode) {
                        params.set('tenant', tenantCode);
                    }
                    if (tourId) {
                        params.set('tour_id', tourId);
                    }
                    var query = params.toString();
                    return query ? (baseUrl + '?' + query) : baseUrl;
                };

                var refreshPageSection = function(nextUrl) {
                    if (!ajaxContainer) {
                        window.location.href = nextUrl;
                        return;
                    }

                    if (switcher) {
                        switcher.disabled = true;
                    }
                    if (picker) {
                        picker.disabled = true;
                    }
                    ajaxContainer.classList.add('opacity-75');

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load capacity page.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshContainer = doc.getElementById('tourDayCapacityAjaxContainer');
                            if (!freshContainer) {
                                throw new Error('Missing refreshed capacity content.');
                            }
                            ajaxContainer.outerHTML = freshContainer.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initCapacityAjax();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            if (switcher) {
                                switcher.disabled = false;
                            }
                            if (picker) {
                                picker.disabled = false;
                            }
                            ajaxContainer.classList.remove('opacity-75');
                        });
                };

                if (switcher && !switcher.dataset.ajaxBound) {
                    switcher.dataset.ajaxBound = '1';
                    switcher.addEventListener('change', function() {
                        var selectedTour = picker ? picker.value : '';
                        refreshPageSection(buildUrl(this.value, selectedTour));
                    });
                }

                if (picker && !picker.dataset.ajaxBound) {
                    picker.dataset.ajaxBound = '1';
                    picker.addEventListener('change', function() {
                        var selectedTenant = switcher ? switcher.value : '';
                        refreshPageSection(buildUrl(selectedTenant, this.value));
                    });
                }
            };

            initCapacityAjax();
        })();
    </script>
    @include('partials.onboarding.page-tour-config', [
        'pageId' => 'tour-day-capacity',
        'steps' => [
            ['target' => 'tour-picker', 'title' => __('translation.onboarding-tour-capacity-picker-title'), 'text' => __('translation.onboarding-tour-capacity-picker-text'), 'on' => 'bottom'],
            ['target' => 'capacity-form', 'title' => __('translation.onboarding-tour-capacity-form-title'), 'text' => __('translation.onboarding-tour-capacity-form-text'), 'on' => 'right'],
            ['target' => 'capacity-save', 'title' => __('translation.onboarding-tour-capacity-save-title'), 'text' => __('translation.onboarding-tour-capacity-save-text'), 'on' => 'top'],
        ],
    ])
    {{-- Shepherd.js tour onboarding (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §5.1 + Phase E). --}}
    <script src="{{ URL::asset('build/libs/shepherd.js/js/shepherd.min.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/onboarding/_tour-helper.js') }}"></script>
    <script src="{{ URL::asset('build/js/pages/onboarding/tour-day-capacity.tour.js') }}"></script>
@endsection
