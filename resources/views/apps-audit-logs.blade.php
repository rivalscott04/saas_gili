@extends('layouts.master')

@section('title')
    Audit Logs
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Operations & Resources
        @endslot
        @slot('title')
            Audit Logs
        @endslot
    @endcomponent

    <div class="card">
        <div class="card-body">
            <form method="GET" action="{{ route('tenant-audit-logs.index') }}" class="row g-3 mb-4">
                @if($showTenantSwitcher)
                    <div class="col-md-3">
                        <label class="form-label">Tenant</label>
                        <select name="tenant" class="form-select">
                            @foreach($availableTenants as $tenantOption)
                                <option value="{{ $tenantOption->code }}" {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                    {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                </option>
                            @endforeach
                        </select>
                    </div>
                @endif
                <div class="col-md-3">
                    <label class="form-label">Tour</label>
                    <select name="tour_id" class="form-select">
                        <option value="">Semua tour</option>
                        @foreach($tours as $tour)
                            <option value="{{ $tour->id }}" {{ (string) $filters['tour_id'] === (string) $tour->id ? 'selected' : '' }}>
                                {{ $tour->name }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-md-2">
                    <label class="form-label">From</label>
                    <input type="date" name="from" value="{{ $filters['from'] }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">To</label>
                    <input type="date" name="to" value="{{ $filters['to'] }}" class="form-control">
                </div>
                <div class="col-md-2">
                    <label class="form-label">Event</label>
                    <select name="event_type" class="form-select">
                        <option value="">Semua event</option>
                        @foreach($eventTypes as $eventType)
                            <option value="{{ $eventType }}" {{ $filters['event_type'] === $eventType ? 'selected' : '' }}>
                                {{ $eventType }}
                            </option>
                        @endforeach
                    </select>
                </div>
                <div class="col-12">
                    <button type="submit" class="btn btn-primary">Filter</button>
                </div>
            </form>

            <div class="table-responsive">
                <table class="table table-sm align-middle">
                    <thead class="table-light">
                        <tr>
                            <th>Waktu</th>
                            <th>Event</th>
                            <th>Tour</th>
                            <th>Service Date</th>
                            <th>Actor</th>
                            <th>Entity</th>
                            <th>Context</th>
                        </tr>
                    </thead>
                    <tbody>
                        @forelse($logs as $log)
                            <tr>
                                <td>{{ optional($log->occurred_at)->format('d M Y H:i:s') }}</td>
                                <td><span class="badge bg-info-subtle text-info">{{ $log->event_type }}</span></td>
                                <td>{{ $log->tour?->name ?? '-' }}</td>
                                <td>{{ optional($log->service_date)->format('Y-m-d') ?? '-' }}</td>
                                <td>{{ $log->actor?->name ?? 'system' }}</td>
                                <td>{{ $log->entity_type }}{{ $log->entity_id ? '#'.$log->entity_id : '' }}</td>
                                <td><code>{{ json_encode($log->context, JSON_UNESCAPED_UNICODE) }}</code></td>
                            </tr>
                        @empty
                            <tr>
                                <td colspan="7" class="text-center text-muted py-4">Belum ada audit log.</td>
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
@endsection
