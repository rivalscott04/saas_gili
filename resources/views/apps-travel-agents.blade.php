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
                        <a href="{{ route('channel-sync.index', $showTenantSwitcher ? ['tenant' => $tenant->code] : []) }}"
                            class="btn btn-soft-primary btn-sm">
                            <i class="ri-refresh-line align-bottom me-1"></i>{{ __('translation.channel-sync') }}
                        </a>
                        <span class="badge bg-primary-subtle text-primary">{{ $tenant->name }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($gygPlatformIntegratorEnabled ?? false)
                        <div class="alert alert-info border-0 mb-4" role="status">
                            <i class="ri-information-line me-1 align-bottom"></i>{{ __('translation.gyg-platform-managed-banner') }}
                        </div>
                    @endif
                    @if ($showTenantSwitcher)
                        <div class="row mb-4">
                            <div class="col-lg-4">
                                <label class="form-label">{{ __('translation.tenant') }}</label>
                                <select class="form-select" id="travelAgentTenantSwitcher">
                                    @foreach ($availableTenants as $tenantOption)
                                        <option value="{{ $tenantOption->code }}"
                                            {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                            {{ $tenantOption->name }}
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
                                $isGyg = \App\Support\GygPlatformIntegrator::isGetYourGuideAgent($travelAgent);
                                $isGygIntegratorEligible = $isGyg && \App\Support\GygPlatformIntegrator::tenantIsAutoConnectEligible($tenant);
                                if ($isGygIntegratorEligible && strtolower((string) $status) !== 'connected') {
                                    $status = 'connected';
                                }
                                $isAirbnb = strtolower((string) $travelAgent->code) === 'airbnb';
                                $isAirbnbOAuthConnected = $isAirbnb
                                    && \App\Support\AirbnbPlatformIntegrator::usesOAuth($connection);
                                $isPlatformManaged = strtolower((string) $status) === 'connected'
                                    && ($isGygIntegratorEligible || \App\Support\GygPlatformIntegrator::isPlatformManaged($connection));
                                $hideResellerSelfService = \App\Support\GygPlatformIntegrator::shouldHideResellerSelfServiceUi($travelAgent, $connection, $tenant)
                                    || ($isAirbnb && $isAirbnbOAuthConnected);
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
                            <div class="col-xl-4 col-lg-6" id="travel-agent-{{ $travelAgent->code }}">
                                <div class="card border h-100 {{ request('agent') === $travelAgent->code ? 'border-primary shadow-sm' : '' }}">
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
                                            @if ($isAirbnbOAuthConnected)
                                                <p class="text-muted small mb-2">
                                                    <span class="badge bg-success-subtle text-success me-1">{{ __('translation.airbnb-oauth-badge') }}</span>
                                                    {{ __('translation.airbnb-oauth-card-hint') }}
                                                </p>
                                            @endif
                                            @if ($isPlatformManaged)
                                                <p class="text-muted small mb-2">
                                                    <span class="badge bg-info-subtle text-info me-1">{{ __('translation.gyg-platform-managed-badge') }}</span>
                                                    {{ __('translation.gyg-platform-managed-card-hint') }}
                                                </p>
                                            @endif
                                            <div class="d-flex justify-content-between mb-1">
                                                <small class="text-muted">
                                                    @if ($isAirbnb)
                                                        {{ __('translation.airbnb-host-id-label') }}
                                                    @else
                                                        {{ __('translation.gyg-supplier-id-label') }}
                                                    @endif
                                                </small>
                                                <small class="fw-medium">
                                                    @if ($isPlatformManaged)
                                                        {{ \App\Support\GygPlatformIntegrator::supplierIdFromConnection($connection, $tenant) }}
                                                    @elseif ($isAirbnbOAuthConnected)
                                                        {{ \App\Support\AirbnbPlatformIntegrator::hostUserId($connection) ?: '—' }}
                                                    @else
                                                        {{ $connection?->account_reference ?: '-' }}
                                                    @endif
                                                </small>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">{{ __('translation.last-checked') }}</small>
                                                <small class="fw-medium">{{ $connection?->last_checked_at?->format('d M Y H:i') ?: '-' }}</small>
                                            </div>
                                        </div>

                                        <div class="mt-auto d-flex gap-2 flex-wrap">
                                            @if ($hideResellerSelfService)
                                                @if ($isPlatformManaged)
                                                    <span class="text-muted small">
                                                        <i class="ri-checkbox-circle-line text-success align-bottom me-1"></i>{{ __('translation.gyg-platform-managed-supplier-id', ['id' => \App\Support\GygPlatformIntegrator::supplierIdFromConnection($connection, $tenant)]) }}
                                                    </span>
                                                @else
                                                    <span class="text-muted small">{{ __('translation.channel-reseller-not-active') }}</span>
                                                @endif
                                            @else
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
                                                @if ($isAirbnb && ($airbnbOAuthEnabled ?? false) && ! $isAirbnbOAuthConnected)
                                                    @can('manageConnection', $travelAgent)
                                                        <a href="{{ route('travel-agents.airbnb.connect', ['travelAgent' => $travelAgent, 'tenant' => $showTenantSwitcher ? $tenant->code : null]) }}"
                                                            class="btn btn-danger btn-sm ms-auto">
                                                            <i class="ri-login-circle-line align-bottom me-1"></i>{{ __('translation.airbnb-connect') }}
                                                        </a>
                                                    @endcan
                                                @endif
                                                @canany(['manageConnection', 'testConnection'], $travelAgent)
                                                    <button type="button" class="btn btn-primary btn-sm {{ ($isAirbnb && ! $isAirbnbOAuthConnected) ? '' : 'ms-auto' }}"
                                                        data-bs-toggle="modal" data-bs-target="#travelAgentModal{{ $travelAgent->id }}">
                                                        {{ $isAirbnb ? __('translation.airbnb-manage') : __('translation.manage') }}
                                                    </button>
                                                @endcanany
                                            @endif
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
            $isAirbnbModal = strtolower((string) $travelAgent->code) === 'airbnb';
            $isAirbnbOAuthConnectedModal = $isAirbnbModal
                && \App\Support\AirbnbPlatformIntegrator::usesOAuth($connection);
            $isGygPlatformForm = \App\Support\GygPlatformIntegrator::isGetYourGuideAgent($travelAgent)
                && \App\Support\GygPlatformIntegrator::tenantIsAutoConnectEligible($tenant);
            $isPlatformManaged = \App\Support\GygPlatformIntegrator::isPlatformManaged($connection);
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
                            @if ($isAirbnbModal)
                                <div class="alert alert-light border mb-3">
                                    <h6 class="alert-heading mb-2">{{ __('translation.airbnb-oauth-modal-title') }}</h6>
                                    <p class="mb-3 small text-muted">{{ __('translation.airbnb-oauth-modal-body') }}</p>
                                    @if ($airbnbOAuthEnabled ?? false)
                                        @if ($isAirbnbOAuthConnectedModal)
                                            <p class="mb-2 small">
                                                {{ __('translation.airbnb-host-id-label') }}:
                                                <strong>{{ \App\Support\AirbnbPlatformIntegrator::hostUserId($connection) ?: '—' }}</strong>
                                            </p>
                                            @can('testConnection', $travelAgent)
                                                <form method="POST" action="{{ route('travel-agents.test', $travelAgent) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                                    <button type="submit" class="btn btn-soft-info btn-sm">
                                                        {{ __('translation.test-connection') }}
                                                    </button>
                                                </form>
                                            @endcan
                                        @else
                                            @can('manageConnection', $travelAgent)
                                                <a href="{{ route('travel-agents.airbnb.connect', ['travelAgent' => $travelAgent, 'tenant' => $showTenantSwitcher ? $tenant->code : null]) }}"
                                                    class="btn btn-danger btn-sm">
                                                    <i class="ri-login-circle-line align-bottom me-1"></i>{{ __('translation.airbnb-connect') }}
                                                </a>
                                            @endcan
                                        @endif
                                    @else
                                        <p class="mb-0 small text-warning">{{ __('translation.airbnb-oauth-not-configured') }}</p>
                                    @endif
                                </div>
                                @if ($isAirbnbOAuthConnectedModal)
                                    @can('manageConnection', $travelAgent)
                                        <form method="POST" action="{{ route('travel-agents.disconnect', $travelAgent) }}">
                                            @csrf
                                            <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                            <button type="submit" class="btn btn-soft-danger btn-sm">
                                                {{ __('translation.airbnb-disconnect') }}
                                            </button>
                                        </form>
                                    @endcan
                                @endif
                            @elseif ($isGygPlatformForm)
                                <div class="alert alert-success border-0">
                                    <h6 class="alert-heading mb-2">{{ __('translation.gyg-platform-managed-modal-title') }}</h6>
                                    <p class="mb-2 small">{{ __('translation.gyg-platform-managed-modal-body') }}</p>
                                    <p class="mb-0 small fw-medium">
                                        {{ __('translation.gyg-platform-managed-supplier-id', ['id' => \App\Support\GygPlatformIntegrator::supplierIdFromConnection($connection, $tenant)]) }}
                                    </p>
                                </div>
                            @endif
                            @if (! $isAirbnbModal)
                            <form method="POST" action="{{ route('travel-agents.connect', $travelAgent) }}"
                                id="travelAgentConnectForm{{ $travelAgent->id }}">
                                @csrf
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                @if ($isGygPlatformForm)
                                    <p class="text-muted small">{{ __('translation.gyg-platform-managed-advanced-hint') }}</p>
                                @endif
                                <div class="row g-3">
                                    <div class="col-lg-12">
                                        <label class="form-label">{{ __('translation.api-key-label') }}</label>
                                        <input type="text" class="form-control" name="api_key"
                                            placeholder="{{ $isGygPlatformForm ? __('translation.gyg-platform-managed-api-key-placeholder') : 'Paste API key' }}"
                                            @if (! $isGygPlatformForm) required @endif>
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('translation.api-secret') }}</label>
                                        <input type="text" class="form-control" name="api_secret" placeholder="Optional secret">
                                    </div>
                                    <div class="col-lg-6">
                                        <label class="form-label">{{ __('translation.account-ref') }}</label>
                                        <input type="text" class="form-control" name="account_reference"
                                            value="{{ $isGygPlatformForm ? \App\Support\GygPlatformIntegrator::supplierIdFromConnection($connection, $tenant) : ($connection?->account_reference) }}"
                                            placeholder="Merchant / Supplier ID"
                                            @if (! $isGygPlatformForm) required @endif
                                            @if ($isGygPlatformForm) readonly @endif>
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
                                            <button type="submit" class="btn btn-success">
                                                {{ $isGygPlatformForm ? __('translation.gyg-platform-managed-refresh') : __('translation.save-connect') }}
                                            </button>
                                        @endcan
                                    </div>
                                </div>
                            </form>
                            @if ($connection && ! $isPlatformManaged)
                                @can('manageConnection', $travelAgent)
                                    <form method="POST" action="{{ route('travel-agents.disconnect', $travelAgent) }}" class="mt-2">
                                        @csrf
                                        <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                        <button type="submit" class="btn btn-soft-danger">{{ __('translation.disconnect') }}</button>
                                    </form>
                                @endcan
                            @endif
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

            var agentFocus = @json(request('agent'));
            if (agentFocus) {
                var el = document.getElementById('travel-agent-' + agentFocus);
                if (el) {
                    el.scrollIntoView({ behavior: 'smooth', block: 'nearest' });
                }
            }
        })();
    </script>
@endsection
