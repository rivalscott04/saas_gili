@php
    $platform = $superadminPlatform ?? [];
    $geography = $channelGeography ?? [];
    $liveUsers = $liveUsersGeography ?? [];
@endphp

<div class="row">
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="fw-medium text-muted mb-0">{{ __('translation.tenants') }}</p>
                <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($platform['total_tenants'] ?? 0) }}</h2>
                <p class="mb-0 text-muted small">{{ __('translation.superadmin-kpi-active-tenants', ['count' => number_format($platform['active_tenants'] ?? 0)]) }}</p>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="fw-medium text-muted mb-0">{{ __('translation.superadmin-kpi-users') }}</p>
                <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($platform['total_users'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="fw-medium text-muted mb-0">{{ __('translation.superadmin-kpi-ota-bookings') }}</p>
                <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($platform['ota_bookings'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
    <div class="col-xl-3 col-md-6">
        <div class="card card-animate">
            <div class="card-body">
                <p class="fw-medium text-muted mb-0">{{ __('translation.superadmin-kpi-gyg-connections') }}</p>
                <h2 class="mt-3 ff-secondary fw-semibold">{{ number_format($platform['gyg_connections'] ?? 0) }}</h2>
            </div>
        </div>
    </div>
</div>

<div class="row">
    <div class="col-xl-6">
        <div class="card card-height-100">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">{{ __('translation.superadmin-live-users-map-title') }}</h4>
            </div>
            <div class="card-body">
                @if (! ($liveUsers['uses_live_data'] ?? false))
                    <div class="alert alert-info border-0 mb-3 py-2" role="alert">
                        {{ __('translation.superadmin-no-live-users-hint') }}
                    </div>
                @endif

                <div id="users-by-country"
                    data-colors='["--vz-light"]'
                    data-live-users-geography='@json($liveUsers)'
                    class="text-center"
                    style="height: 252px"></div>

                <div class="table-responsive table-card mt-3">
                    <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-1">
                        <thead class="text-muted border-dashed border border-start-0 border-end-0 bg-light-subtle">
                            <tr>
                                <th>{{ __('translation.superadmin-live-users-country') }}</th>
                                <th style="width: 30%;">{{ __('translation.superadmin-live-users-sessions') }}</th>
                                <th style="width: 30%;">{{ __('translation.superadmin-live-users-users') }}</th>
                            </tr>
                        </thead>
                        <tbody class="border-0">
                            @forelse(($liveUsers['rows'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['country'] }}</td>
                                    <td>{{ number_format($row['sessions']) }}</td>
                                    <td>{{ number_format($row['users']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted text-center py-2">{{ __('translation.no-data-selected-filter') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>

    <div class="col-xl-6">
        <div class="card card-height-100">
            <div class="card-header align-items-center d-flex">
                <h4 class="card-title mb-0 flex-grow-1">{{ __('translation.superadmin-ota-bars-title') }}</h4>
            </div>
            <div class="card-body p-0">
                @if (! ($geography['uses_live_data'] ?? false))
                    <div class="alert alert-info border-0 m-3 mb-0 py-2" role="alert">
                        {{ __('translation.superadmin-no-ota-data-hint') }}
                    </div>
                @endif
                <div id="countries_charts"
                    data-colors='["--vz-info", "--vz-danger", "--vz-info", "--vz-info", "--vz-info"]'
                    data-ota-geography='@json($geography)'
                    class="apex-charts" dir="ltr"></div>
            </div>
            <div class="card-body pt-0">
                <div class="table-responsive table-card">
                    <table class="table table-borderless table-sm table-centered align-middle table-nowrap mb-0">
                        <thead class="text-muted border-dashed border border-start-0 border-end-0 bg-light-subtle">
                            <tr>
                                <th>{{ __('translation.superadmin-ota-channel') }}</th>
                                <th style="width: 30%;">{{ __('translation.bookings') }}</th>
                                <th style="width: 30%;">{{ __('translation.guests') }}</th>
                            </tr>
                        </thead>
                        <tbody class="border-0">
                            @forelse(($geography['channel_rows'] ?? []) as $row)
                                <tr>
                                    <td>{{ $row['channel'] }}</td>
                                    <td>{{ number_format($row['bookings']) }}</td>
                                    <td>{{ number_format($row['guests']) }}</td>
                                </tr>
                            @empty
                                <tr>
                                    <td colspan="3" class="text-muted text-center py-2">{{ __('translation.no-data-selected-filter') }}</td>
                                </tr>
                            @endforelse
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </div>
</div>
