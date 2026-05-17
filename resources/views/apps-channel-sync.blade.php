@extends('layouts.master')

@section('title')
    {{ __('translation.channel-sync-title') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.sales-channels') }}
        @endslot
        @slot('title')
            {{ __('translation.channel-sync') }}
        @endslot
    @endcomponent

    <div id="channelSyncAjaxContainer">
        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0">
                        <div class="d-flex flex-wrap align-items-center gap-2">
                            <div class="flex-grow-1">
                                <h5 class="card-title mb-1">{{ __('translation.channel-sync-title') }}</h5>
                                <p class="text-muted small mb-0">{{ __('translation.channel-sync-description') }}</p>
                            </div>
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
                                    <select class="form-select" id="channelSyncTenantSwitcher">
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

                        <div class="row g-3 mb-4">
                            <div class="col-12">
                                <div class="card border shadow-none mb-0">
                                    <div class="card-body">
                                        <div class="d-flex flex-wrap justify-content-between align-items-start gap-3">
                                            <div>
                                                <h6 class="mb-1">{{ __('translation.channel-sync-summary-window') }}</h6>
                                                <p class="text-muted small mb-0">{{ __('translation.channel-sync-default-range-info') }}</p>
                                            </div>
                                            @if ($canViewSyncLogs)
                                                <a href="{{ route('channel-sync-logs.index', $showTenantSwitcher ? ['tenant' => $tenant->code] : []) }}"
                                                    class="btn btn-soft-secondary btn-sm">
                                                    <i class="ri-list-unordered align-bottom me-1"></i>{{ __('translation.view-sync-logs') }}
                                                </a>
                                            @endif
                                        </div>
                                        <div class="row text-center mt-3 g-2">
                                            <div class="col-6 col-md-3">
                                                <div class="p-2 border rounded">
                                                    <div class="fs-5 fw-semibold text-success">{{ $stats['success'] }}</div>
                                                    <small class="text-muted">{{ __('translation.channel-sync-stat-success') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="p-2 border rounded">
                                                    <div class="fs-5 fw-semibold text-danger">{{ $stats['error'] }}</div>
                                                    <small class="text-muted">{{ __('translation.channel-sync-stat-error') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="p-2 border rounded">
                                                    <div class="fs-5 fw-semibold text-info">{{ $stats['queued'] }}</div>
                                                    <small class="text-muted">{{ __('translation.channel-sync-stat-queued') }}</small>
                                                </div>
                                            </div>
                                            <div class="col-6 col-md-3">
                                                <div class="p-2 border rounded">
                                                    <div class="fs-6 fw-semibold text-body">
                                                        {{ $stats['last_run_at'] ? \Illuminate\Support\Carbon::parse($stats['last_run_at'])->diffForHumans() : '—' }}
                                                    </div>
                                                    <small class="text-muted">{{ __('translation.channel-sync-stat-last') }}</small>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>

                        <div class="row">
                            @forelse ($travelAgents as $travelAgent)
                                @php
                                    $connection = $travelAgent->tenantConnections->first();
                                    $status = $connection?->status ?? 'disconnected';
                                    $isConnected = strtolower((string) $status) === 'connected';
                                    $isPlatformManaged = $isConnected && \App\Support\GygPlatformIntegrator::isPlatformManaged($connection);
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
                                    $lastRun = $lastRunsByAgent->get($travelAgent->id);
                                    $lastRunAt = $lastRun?->occurred_at ?? $lastRun?->created_at;
                                    $lastRunStatus = strtolower((string) ($lastRun?->status ?? ''));
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
                                            @if ($isPlatformManaged)
                                                <p class="text-muted small mb-3">
                                                    <span class="badge bg-info-subtle text-info me-1">{{ __('translation.gyg-platform-managed-badge') }}</span>
                                                    {{ __('translation.channel-sync-inbound-only') }}
                                                </p>
                                            @endif

                                            <div class="mb-3">
                                                <div class="d-flex justify-content-between mb-1">
                                                    <small class="text-muted">{{ __('translation.channel-sync-last-run-label') }}</small>
                                                    <small class="fw-medium">
                                                        @if ($lastRunAt)
                                                            <span class="@if ($lastRunStatus === 'error') text-danger @elseif ($lastRunStatus === 'success') text-success @endif">
                                                                {{ \Illuminate\Support\Carbon::parse($lastRunAt)->format('d M Y H:i') }}
                                                            </span>
                                                        @else
                                                            —
                                                        @endif
                                                    </small>
                                                </div>
                                                <div class="d-flex justify-content-between">
                                                    <small class="text-muted">{{ __('translation.channel-sync-date-from') }} → {{ __('translation.channel-sync-date-to') }}</small>
                                                    <small class="fw-medium">{{ __('translation.channel-sync-default-range-badge') }}</small>
                                                </div>
                                            </div>

                                            <div class="mt-auto d-flex gap-2 flex-wrap">
                                                @if (! $isConnected)
                                                    <a href="{{ route('travel-agents.index', $showTenantSwitcher ? ['tenant' => $tenant->code] : []) }}"
                                                        class="btn btn-soft-warning btn-sm">
                                                        <i class="ri-link-m align-bottom me-1"></i>{{ __('translation.channel-sync-connect-first') }}
                                                    </a>
                                                @elseif ($isPlatformManaged)
                                                    <span class="text-muted small">
                                                        <i class="ri-arrow-left-down-line align-bottom me-1"></i>{{ __('translation.gyg-platform-managed-supplier-id', ['id' => \App\Support\GygPlatformIntegrator::supplierIdFromConnection($connection, $tenant)]) }}
                                                    </span>
                                                @else
                                                    @if ($canTriggerSync)
                                                        <form method="POST"
                                                            action="{{ route('channel-sync.pull', $travelAgent) }}"
                                                            class="d-inline">
                                                            @csrf
                                                            @if ($showTenantSwitcher)
                                                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                                            @endif
                                                            <button type="submit" class="btn btn-primary btn-sm">
                                                                <i class="ri-refresh-line align-bottom me-1"></i>{{ __('translation.channel-sync-pull-now') }}
                                                            </button>
                                                        </form>
                                                        <button type="button" class="btn btn-soft-primary btn-sm"
                                                            data-bs-toggle="modal"
                                                            data-bs-target="#channelSyncRangeModal{{ $travelAgent->id }}">
                                                            <i class="ri-calendar-line align-bottom me-1"></i>{{ __('translation.channel-sync-pull-range') }}
                                                        </button>
                                                    @else
                                                        <span class="btn btn-soft-secondary btn-sm disabled"
                                                            title="{{ __('translation.channel-sync-no-permission') }}">
                                                            <i class="ri-lock-line align-bottom me-1"></i>{{ __('translation.channel-sync-pull-now') }}
                                                        </span>
                                                    @endif
                                                @endif
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            @empty
                                <div class="col-12">
                                    <div class="text-center text-muted py-4">
                                        {{ __('translation.channel-sync-no-connections') }}
                                        <div class="mt-3">
                                            <a href="{{ route('travel-agents.index', $showTenantSwitcher ? ['tenant' => $tenant->code] : []) }}"
                                                class="btn btn-primary btn-sm">
                                                <i class="ri-link-m align-bottom me-1"></i>{{ __('translation.manage-connections') }}
                                            </a>
                                        </div>
                                    </div>
                                </div>
                            @endforelse
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <div class="row">
            <div class="col-12">
                <div class="card">
                    <div class="card-header border-0 d-flex flex-wrap align-items-center gap-2">
                        <h5 class="card-title mb-0 flex-grow-1">{{ __('translation.channel-sync-recent-runs') }}</h5>
                        @if ($canViewSyncLogs)
                            <a href="{{ route('channel-sync-logs.index', $showTenantSwitcher ? ['tenant' => $tenant->code] : []) }}"
                                class="btn btn-soft-secondary btn-sm">
                                <i class="ri-list-unordered align-bottom me-1"></i>{{ __('translation.view-sync-logs') }}
                            </a>
                        @endif
                    </div>
                    <div class="card-body pt-0">
                        <div class="table-responsive table-card">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light text-muted">
                                    <tr>
                                        <th>{{ __('translation.channel-sync-time') }}</th>
                                        <th>{{ __('translation.channel-sync-channel') }}</th>
                                        <th>{{ __('translation.channel-sync-event') }}</th>
                                        <th>{{ __('translation.channel-sync-status-col') }}</th>
                                        <th>{{ __('translation.channel-sync-message-col') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @forelse ($recentRuns as $run)
                                        @php
                                            $runStatus = strtolower((string) $run->status);
                                            $rowBadge = $runStatus === 'success'
                                                ? 'bg-success-subtle text-success'
                                                : ($runStatus === 'error'
                                                    ? 'bg-danger-subtle text-danger'
                                                    : 'bg-secondary-subtle text-secondary');
                                        @endphp
                                        <tr>
                                            <td>{{ ($run->occurred_at ?? $run->created_at)?->format('d M Y H:i:s') }}</td>
                                            <td>{{ $run->travelAgent?->name ?? '—' }}</td>
                                            <td><code class="text-muted">{{ $run->event_type }}</code></td>
                                            <td><span class="badge {{ $rowBadge }}">{{ ucfirst($run->status) }}</span></td>
                                            <td class="text-muted">{{ \Illuminate\Support\Str::limit((string) $run->message, 120) }}</td>
                                        </tr>
                                    @empty
                                        <tr>
                                            <td colspan="5" class="text-center text-muted py-4">
                                                {{ __('translation.channel-sync-empty-runs') }}
                                            </td>
                                        </tr>
                                    @endforelse
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    @foreach ($travelAgents as $travelAgent)
        @php
            $connection = $travelAgent->tenantConnections->first();
            $isConnected = strtolower((string) ($connection?->status ?? '')) === 'connected';
        @endphp
        @if ($isConnected && $canTriggerSync)
            <div class="modal fade" id="channelSyncRangeModal{{ $travelAgent->id }}" tabindex="-1"
                aria-labelledby="channelSyncRangeModalLabel{{ $travelAgent->id }}" aria-hidden="true">
                <div class="modal-dialog modal-dialog-centered">
                    <div class="modal-content">
                        <form method="POST" action="{{ route('channel-sync.pull', $travelAgent) }}">
                            @csrf
                            @if ($showTenantSwitcher)
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                            @endif
                            <div class="modal-header">
                                <h5 class="modal-title" id="channelSyncRangeModalLabel{{ $travelAgent->id }}">
                                    {{ __('translation.channel-sync-pull-modal-title') }} — {{ $travelAgent->name }}
                                </h5>
                                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                            </div>
                            <div class="modal-body">
                                <p class="text-muted small">{{ __('translation.channel-sync-pull-modal-help') }}</p>
                                <div class="row g-3">
                                    <div class="col-md-6">
                                        <label class="form-label" for="dateFrom{{ $travelAgent->id }}">{{ __('translation.channel-sync-date-from') }}</label>
                                        <input type="date" class="form-control"
                                            id="dateFrom{{ $travelAgent->id }}" name="date_from"
                                            value="{{ now()->toDateString() }}">
                                    </div>
                                    <div class="col-md-6">
                                        <label class="form-label" for="dateTo{{ $travelAgent->id }}">{{ __('translation.channel-sync-date-to') }}</label>
                                        <input type="date" class="form-control"
                                            id="dateTo{{ $travelAgent->id }}" name="date_to"
                                            value="{{ now()->addDays(30)->toDateString() }}">
                                    </div>
                                    <div class="col-12">
                                        <div class="form-check">
                                            <input class="form-check-input" type="checkbox" value="1"
                                                id="forceRepull{{ $travelAgent->id }}" name="force_repull">
                                            <label class="form-check-label" for="forceRepull{{ $travelAgent->id }}">
                                                {{ __('translation.channel-sync-force-repull') }}
                                            </label>
                                            <div class="form-text">{{ __('translation.channel-sync-force-repull-help') }}</div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="modal-footer">
                                <button type="button" class="btn btn-light" data-bs-dismiss="modal">
                                    {{ __('translation.cancel') }}
                                </button>
                                <button type="submit" class="btn btn-primary">
                                    <i class="ri-refresh-line align-bottom me-1"></i>{{ __('translation.channel-sync-submit') }}
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        @endif
    @endforeach
@endsection

@section('script')
    <script>
        (function () {
            var switcher = document.getElementById('channelSyncTenantSwitcher');
            if (!switcher) {
                return;
            }
            switcher.addEventListener('change', function () {
                var nextUrl = "{{ route('channel-sync.index') }}" + '?tenant=' + encodeURIComponent(this.value);
                window.location.href = nextUrl;
            });
        })();
    </script>
@endsection
