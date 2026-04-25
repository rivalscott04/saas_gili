@extends('layouts.master')

@section('title')
    {{ __('translation.travel-agents') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.sales-channels') }}
        @endslot
        @slot('title')
            {{ __('translation.travel-agents') }}
        @endslot
    @endcomponent

    <div id="travelAgentsAjaxContainer">
    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1">{{ __('translation.ota-reseller-connections') }}</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $tenant->name }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($showTenantSwitcher)
                        <div class="row mb-4">
                            <div class="col-lg-4">
                                <label class="form-label">{{ __('translation.tenant') }}</label>
                                <select class="form-select" id="travelAgentTenantSwitcher">
                                    @foreach ($availableTenants as $tenantOption)
                                        <option value="{{ $tenantOption->code }}"
                                            {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                            {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif

                    @php
                        $gygMetrics = $gygMetrics ?? ['window_days' => 7, 'inbound_success' => 0, 'inbound_error' => 0, 'outbound_success' => 0, 'outbound_error' => 0];
                        $failedBookingRetryCount = $failedBookingRetryCount ?? 0;
                        $canViewSyncLogs = $canViewSyncLogs ?? false;
                        $canRetryFailedJobs = $canRetryFailedJobs ?? false;
                    @endphp
                    <div class="row g-3 mb-4">
                        <div class="col-12">
                            <div class="card border shadow-none mb-0">
                                <div class="card-body">
                                    <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                        <div>
                                            <h6 class="mb-1">{{ __('translation.gyg-integration-health') }}</h6>
                                            <p class="text-muted small mb-0">{{ __('translation.gyg-metrics-window', ['days' => $gygMetrics['window_days']]) }}</p>
                                        </div>
                                        @if ($canRetryFailedJobs && $failedBookingRetryCount > 0)
                                            <form method="POST" action="{{ route('travel-agents.retry-failed-sync') }}" class="d-inline">
                                                @csrf
                                                @if ($showTenantSwitcher)
                                                    <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                                @endif
                                                <button type="submit" class="btn btn-warning btn-sm">
                                                    {{ __('translation.gyg-retry-failed-sync') }} ({{ $failedBookingRetryCount }})
                                                </button>
                                            </form>
                                        @endif
                                    </div>
                                    <div class="row text-center mt-3 g-2">
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded">
                                                <div class="fs-5 fw-semibold text-success">{{ $gygMetrics['inbound_success'] }}</div>
                                                <small class="text-muted">{{ __('translation.gyg-inbound-ok') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded">
                                                <div class="fs-5 fw-semibold text-danger">{{ $gygMetrics['inbound_error'] }}</div>
                                                <small class="text-muted">{{ __('translation.gyg-inbound-err') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded">
                                                <div class="fs-5 fw-semibold text-success">{{ $gygMetrics['outbound_success'] }}</div>
                                                <small class="text-muted">{{ __('translation.gyg-outbound-ok') }}</small>
                                            </div>
                                        </div>
                                        <div class="col-6 col-md-3">
                                            <div class="p-2 border rounded">
                                                <div class="fs-5 fw-semibold text-danger">{{ $gygMetrics['outbound_error'] }}</div>
                                                <small class="text-muted">{{ __('translation.gyg-outbound-err') }}</small>
                                            </div>
                                        </div>
                                    </div>
                                    @if ($canViewSyncLogs)
                                        <div class="mt-3">
                                            <a href="{{ route('channel-sync-logs.index', $showTenantSwitcher ? ['tenant' => $tenant->code] : []) }}"
                                                class="btn btn-soft-secondary btn-sm">
                                                <i class="ri-list-unordered align-bottom me-1"></i>{{ __('translation.sync-logs') }}
                                            </a>
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="row">
                        @forelse ($travelAgents as $travelAgent)
                            @php
                                $connection = $travelAgent->tenantConnections->first();
                                $status = $connection?->status ?? 'disconnected';
                                $statusMap = [
                                    'connected' => ['label' => __('translation.connected'), 'class' => 'bg-success-subtle text-success'],
                                    'error' => ['label' => __('translation.error-status'), 'class' => 'bg-danger-subtle text-danger'],
                                    'disconnected' => ['label' => __('translation.disconnected'), 'class' => 'bg-warning-subtle text-warning'],
                                ];
                                $badge = $statusMap[$status] ?? $statusMap['disconnected'];
                                $logo = $brandingMap[$travelAgent->code] ?? [
                                    'label' => strtoupper(substr($travelAgent->code, 0, 2)),
                                    'class' => 'bg-light text-secondary',
                                    'brand_color' => '#6C757D',
                                    'image' => null,
                                ];
                            @endphp
                            <div class="col-xl-4 col-lg-6">
                                <div class="card border h-100">
                                    <div class="card-body d-flex flex-column">
                                        <div class="d-flex align-items-center gap-3 mb-3">
                                            <div class="avatar-sm">
                                                <span class="avatar-title rounded-circle {{ $logo['class'] }} fw-semibold overflow-hidden border"
                                                    style="border-color: {{ $logo['brand_color'] ?? '#6C757D' }} !important; color: {{ $logo['brand_color'] ?? '#6C757D' }};">
                                                    @if (! empty($logo['image']))
                                                        <img src="{{ $logo['image'] }}" alt="{{ $travelAgent->name }} logo"
                                                            style="max-width: 70%; max-height: 70%; object-fit: contain;"
                                                            onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                                        <span style="display: none;">{{ $logo['label'] }}</span>
                                                    @else
                                                        {{ $logo['label'] }}
                                                    @endif
                                                </span>
                                            </div>
                                            <div class="flex-grow-1">
                                                <h5 class="mb-0">{{ $travelAgent->name }}</h5>
                                                <small class="text-muted text-uppercase">{{ $travelAgent->code }}</small>
                                            </div>
                                            <span class="badge {{ $badge['class'] }}">{{ $badge['label'] }}</span>
                                        </div>

                                        <div class="mb-3">
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">{{ __('translation.account-ref') }}</small>
                                                <small class="fw-medium">{{ $connection?->account_reference ?: '-' }}</small>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">{{ __('translation.last-checked') }}</small>
                                                <small class="fw-medium">{{ $connection?->last_checked_at?->format('d M Y H:i') ?: '-' }}</small>
                                            </div>
                                        </div>

                                        <div class="mt-auto d-flex gap-2 flex-wrap">
                                            @if ($travelAgent->signup_url)
                                                <a href="{{ $travelAgent->signup_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-soft-primary btn-sm">
                                                    {{ __('translation.sign-up') }}
                                                </a>
                                            @endif
                                            @if ($travelAgent->docs_url)
                                                <a href="{{ $travelAgent->docs_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-soft-info btn-sm">
                                                    {{ __('translation.docs') }}
                                                </a>
                                            @endif
                                            @canany(['manageConnection', 'testConnection'], $travelAgent)
                                                <button type="button" class="btn btn-primary btn-sm ms-auto"
                                                    data-bs-toggle="modal" data-bs-target="#travelAgentModal{{ $travelAgent->id }}">
                                                    {{ __('translation.manage') }}
                                                </button>
                                            @endcanany
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center text-muted py-4">
                                    {{ __('translation.no-active-travel-agents') }}
                                </div>
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($travelAgents as $travelAgent)
        @php
            $connection = $travelAgent->tenantConnections->first();
        @endphp
        <div class="modal fade" id="travelAgentModal{{ $travelAgent->id }}" tabindex="-1"
            aria-labelledby="travelAgentModalLabel{{ $travelAgent->id }}" aria-hidden="true">
            <div class="modal-dialog modal-lg modal-dialog-centered">
                <div class="modal-content">
                    <div class="modal-header">
                        <h5 class="modal-title" id="travelAgentModalLabel{{ $travelAgent->id }}">
                            {{ __('translation.manage-connection-for') }} {{ $travelAgent->name }}
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        @canany(['manageConnection', 'testConnection'], $travelAgent)
                            <form method="POST" action="{{ route('travel-agents.connect', $travelAgent) }}"
                                id="travelAgentConnectForm{{ $travelAgent->id }}">
                                @csrf
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                <div class="row g-3">
                                    <div class="col-lg-12">
                                        <label class="form-label">{{ __('translation.api-key-label') }}</label>
                                        <input type="text" class="form-control" name="api_key" placeholder="Paste API key" required>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('translation.api-secret') }}</label>
                                        <input type="text" class="form-control" name="api_secret" placeholder="Optional secret">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('translation.account-ref') }}</label>
                                        <input type="text" class="form-control" name="account_reference"
                                            value="{{ $connection?->account_reference }}" placeholder="Merchant / Supplier ID" required>
                                    </div>
                                </div>

                                <div class="d-flex justify-content-between mt-4 flex-wrap gap-2">
                                    <span></span>
                                    <div class="hstack gap-2">
                                        @can('testConnection', $travelAgent)
                                            <button type="submit" formaction="{{ route('travel-agents.test', $travelAgent) }}"
                                                class="btn btn-soft-info">{{ __('translation.test-connection') }}</button>
                                        @endcan
                                        @can('manageConnection', $travelAgent)
                                            <button type="submit" class="btn btn-success">{{ __('translation.save-connect') }}</button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                            @if ($connection)
                                @can('manageConnection', $travelAgent)
                                    <form method="POST" action="{{ route('travel-agents.disconnect', $travelAgent) }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                        <button type="submit" class="btn btn-soft-danger">{{ __('translation.disconnect') }}</button>
                                    </form>
                                @endcan
                            @endif
                        @else
                            <p class="text-muted mb-0">{{ __('translation.travel-agents-view-only-hint') }}</p>
                        @endcanany
                    </div>
                </div>
            </div>
        </div>
    @endforeach
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var initTenantSwitcher = function() {
                var switcher = document.getElementById('travelAgentTenantSwitcher');
                var ajaxContainer = document.getElementById('travelAgentsAjaxContainer');
                if (!switcher || !ajaxContainer) {
                    return;
                }

                switcher.addEventListener('change', function() {
                    var nextUrl = "{{ route('travel-agents.index') }}" + '?tenant=' + encodeURIComponent(this.value);
                    switcher.disabled = true;
                    ajaxContainer.classList.add('opacity-75');

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load travel agents page.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshContainer = doc.getElementById('travelAgentsAjaxContainer');
                            if (!freshContainer) {
                                throw new Error('Missing refreshed travel agents content.');
                            }
                            ajaxContainer.outerHTML = freshContainer.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initTenantSwitcher();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            switcher.disabled = false;
                            ajaxContainer.classList.remove('opacity-75');
                        });
                });
            };

            initTenantSwitcher();
        })();
    </script>
@endsection
