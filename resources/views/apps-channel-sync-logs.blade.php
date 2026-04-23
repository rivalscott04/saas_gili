@extends('layouts.master')

@section('title')
    Channel Sync Logs
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Sales Channels
        @endslot
        @slot('title')
            Sync Logs
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <form method="GET" class="row g-3 mb-3">
                        @if (auth()->user()?->isSuperAdmin())
                            <div class="col-lg-3">
                                <label class="form-label">Tenant</label>
                                <select class="form-select" name="tenant">
                                    <option value="">All Tenants</option>
                                    @foreach ($availableTenants as $tenantOption)
                                        <option value="{{ $tenantOption->code }}"
                                            {{ ($filters['tenant'] ?? '') === (string) $tenantOption->code ? 'selected' : '' }}>
                                            {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        @endif
                        <div class="col-lg-3">
                            <label class="form-label">Travel Agent</label>
                            <select class="form-select" name="agent">
                                <option value="">All Agents</option>
                                @foreach ($travelAgents as $agent)
                                    <option value="{{ $agent->code }}" {{ ($filters['agent'] ?? '') === (string) $agent->code ? 'selected' : '' }}>
                                        {{ $agent->name }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">Status</label>
                            <select class="form-select" name="status">
                                <option value="">All Status</option>
                                <option value="success" {{ $filters['status'] === 'success' ? 'selected' : '' }}>Success</option>
                                <option value="error" {{ $filters['status'] === 'error' ? 'selected' : '' }}>Error</option>
                            </select>
                        </div>
                        <div class="col-lg-4">
                            <label class="form-label">Event Type</label>
                            <input type="text" class="form-control" name="event_type" value="{{ $filters['event_type'] }}"
                                placeholder="e.g webhook.received">
                        </div>
                        <div class="col-lg-2 d-grid">
                            <label class="form-label d-none d-lg-block">&nbsp;</label>
                            <button type="submit" class="btn btn-primary">Filter</button>
                        </div>
                    </form>

                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>Time</th>
                                    <th>Agent</th>
                                    <th>Event</th>
                                    <th>Status</th>
                                    <th>Message</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($logs as $log)
                                    <tr>
                                        <td>{{ $log->occurred_at?->format('d M Y H:i:s') ?? $log->created_at?->format('d M Y H:i:s') }}</td>
                                        <td>{{ $log->travelAgent?->name ?? '-' }}</td>
                                        <td><code>{{ $log->event_type }}</code></td>
                                        <td>
                                            <span class="badge {{ $log->status === 'success' ? 'bg-success-subtle text-success' : 'bg-danger-subtle text-danger' }}">
                                                {{ strtoupper($log->status) }}
                                            </span>
                                        </td>
                                        <td>{{ $log->message }}</td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center text-muted py-4">Belum ada sync logs.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    <div class="mt-3">
                        {{ $logs->links() }}
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
