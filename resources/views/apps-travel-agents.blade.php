@extends('layouts.master')

@section('title')
    Travel Agents
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Sales Channels
        @endslot
        @slot('title')
            Travel Agents
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <h5 class="card-title mb-0 flex-grow-1">OTA & Reseller Connections</h5>
                        <span class="badge bg-primary-subtle text-primary">{{ $tenant->name }}</span>
                    </div>
                </div>
                <div class="card-body">
                    @if ($showTenantSwitcher)
                        <div class="row mb-4">
                            <div class="col-lg-4">
                                <label class="form-label">Tenant</label>
                                <select class="form-select"
                                    onchange="window.location.href='{{ route('travel-agents.index') }}?tenant=' + encodeURIComponent(this.value)">
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

                    <div class="row">
                        @forelse ($travelAgents as $travelAgent)
                            @php
                                $connection = $travelAgent->tenantConnections->first();
                                $status = $connection?->status ?? 'disconnected';
                                $statusMap = [
                                    'connected' => ['label' => 'Connected', 'class' => 'bg-success-subtle text-success'],
                                    'error' => ['label' => 'Error', 'class' => 'bg-danger-subtle text-danger'],
                                    'disconnected' => ['label' => 'Disconnected', 'class' => 'bg-warning-subtle text-warning'],
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
                                                <small class="text-muted">Account Ref</small>
                                                <small class="fw-medium">{{ $connection?->account_reference ?: '-' }}</small>
                                            </div>
                                            <div class="d-flex justify-content-between">
                                                <small class="text-muted">Last Checked</small>
                                                <small class="fw-medium">{{ $connection?->last_checked_at?->format('d M Y H:i') ?: '-' }}</small>
                                            </div>
                                        </div>

                                        <div class="mt-auto d-flex gap-2 flex-wrap">
                                            @if ($travelAgent->signup_url)
                                                <a href="{{ $travelAgent->signup_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-soft-primary btn-sm">
                                                    Sign Up
                                                </a>
                                            @endif
                                            @if ($travelAgent->docs_url)
                                                <a href="{{ $travelAgent->docs_url }}" target="_blank" rel="noopener"
                                                    class="btn btn-soft-info btn-sm">
                                                    Docs
                                                </a>
                                            @endif
                                            <button type="button" class="btn btn-primary btn-sm ms-auto"
                                                data-bs-toggle="modal" data-bs-target="#travelAgentModal{{ $travelAgent->id }}">
                                                Manage
                                            </button>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        @empty
                            <div class="col-12">
                                <div class="text-center text-muted py-4">
                                    Belum ada travel agent aktif.
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
                            Manage {{ $travelAgent->name }} Connection
                        </h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <form method="POST" action="{{ route('travel-agents.connect', $travelAgent) }}"
                            id="travelAgentConnectForm{{ $travelAgent->id }}">
                            @csrf
                            <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                            <div class="row g-3">
                                <div class="col-lg-12">
                                    <label class="form-label">API Key</label>
                                    <input type="text" class="form-control" name="api_key" placeholder="Paste API key" required>
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">API Secret</label>
                                    <input type="text" class="form-control" name="api_secret" placeholder="Optional secret">
                                </div>
                                <div class="col-lg-6">
                                    <label class="form-label">Account Ref</label>
                                    <input type="text" class="form-control" name="account_reference"
                                        value="{{ $connection?->account_reference }}" placeholder="Merchant / Supplier ID" required>
                                </div>
                            </div>

                            <div class="d-flex justify-content-between mt-4 flex-wrap gap-2">
                                <span></span>
                                <div class="hstack gap-2">
                                    <button type="submit" formaction="{{ route('travel-agents.test', $travelAgent) }}"
                                        class="btn btn-soft-info">Test Connection</button>
                                    <button type="submit" class="btn btn-success">Save & Connect</button>
                                </div>
                            </div>
                        </form>
                        @if ($connection)
                            <form method="POST" action="{{ route('travel-agents.disconnect', $travelAgent) }}" class="mt-2">
                                @csrf
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                <button type="submit" class="btn btn-soft-danger">Disconnect</button>
                            </form>
                        @endif
                    </div>
                </div>
            </div>
        </div>
    @endforeach
@endsection
