@extends('layouts.master')

@section('title')
    {{ __('translation.revenue-recap') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.tour-operations') }}
        @endslot
        @slot('title')
            {{ __('translation.revenue-recap') }}
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-body">
                    <div class="alert alert-info mb-4">
                        <div class="d-flex align-items-start gap-2">
                            <i class="ri-information-line fs-5"></i>
                            <div>
                                <div class="fw-semibold">{{ __('translation.how-to-read-recap') }}</div>
                                <div class="small">{{ __('translation.booking-recap-read-help') }}</div>
                            </div>
                        </div>
                    </div>
                    <form method="GET" class="row g-3">
                        <div class="col-lg-3">
                            <label class="form-label">{{ __('translation.specific-date') }}</label>
                            <input type="date" class="form-control" name="specific_date" value="{{ $filters['specific_date'] }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">{{ __('translation.date-from') }}</label>
                            <input type="date" class="form-control" name="date_from" value="{{ $filters['date_from'] }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">{{ __('translation.date-to') }}</label>
                            <input type="date" class="form-control" name="date_to" value="{{ $filters['date_to'] }}">
                        </div>
                        <div class="col-lg-3">
                            <label class="form-label">{{ __('translation.order-source') }}</label>
                            <select class="form-select" name="channel">
                                <option value="">{{ __('translation.all-sources') }}</option>
                                @foreach ($channels as $channel)
                                    <option value="{{ $channel }}" {{ $filters['channel'] === $channel ? 'selected' : '' }}>
                                        {{ strtoupper($channel) }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                        <div class="col-12 d-flex gap-2">
                            <button type="submit" class="btn btn-primary">{{ __('translation.apply-filter') }}</button>
                            <a href="{{ route('bookings.recap') }}" class="btn btn-soft-secondary">{{ __('translation.reset') }}</a>
                            <button type="button" class="btn btn-soft-success" data-bs-toggle="modal" data-bs-target="#recapExportModal">
                                <i class="ri-file-download-line align-bottom me-1"></i>{{ __('translation.export-data') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header d-flex align-items-center">
                    <h5 class="card-title mb-0 flex-grow-1">{{ __('translation.daily-net-trend-idr') }}</h5>
                </div>
                <div class="card-body">
                    <div id="bookingRevenueTrendChart" class="apex-charts" style="min-height: 320px;"></div>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">{{ __('translation.total-bookings') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_bookings']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">{{ __('translation.total-pax') }}</p>
                    <h4 class="mb-0">{{ number_format($summary['total_pax']) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">{{ __('translation.gross-idr') }}</p>
                    <h4 class="mb-0">IDR {{ number_format($summary['gross_idr'], 0) }}</h4>
                </div>
            </div>
        </div>
        <div class="col-xl-3 col-md-6">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-muted mb-1">{{ __('translation.net-revenue-idr') }}</p>
                    <h4 class="mb-0">IDR {{ number_format($summary['net_idr'], 0) }}</h4>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">{{ __('translation.recap-per-order-source') }}</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table table-nowrap align-middle mb-0">
                            <thead class="table-light text-muted">
                                <tr>
                                    <th>{{ __('translation.order-source') }}</th>
                                    <th>{{ __('translation.total-orders') }}</th>
                                    <th>PAX</th>
                                    <th>{{ __('translation.net-revenue-idr') }}</th>
                                    <th>{{ __('translation.avg-per-order-idr') }}</th>
                                    <th>{{ __('translation.contribution') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($perChannel as $row)
                                    @php
                                        $avgNet = ((int) $row->total_bookings) > 0 ? ((float) $row->net_idr / (int) $row->total_bookings) : 0;
                                        $share = (float) ($summary['net_idr'] ?? 0) > 0
                                            ? (((float) $row->net_idr / (float) $summary['net_idr']) * 100)
                                            : 0;
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ strtoupper((string) $row->channel_label) }}</td>
                                        <td>{{ number_format((int) $row->total_bookings) }}</td>
                                        <td>{{ number_format((int) $row->total_pax) }}</td>
                                        <td>IDR {{ number_format((float) $row->net_idr, 0) }}</td>
                                        <td>IDR {{ number_format($avgNet, 0) }}</td>
                                        <td>
                                            <span class="badge bg-info-subtle text-info">
                                                {{ number_format($share, 1) }}%
                                            </span>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center text-muted py-4">{{ __('translation.no-data-selected-filter') }}</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="recapExportModal" tabindex="-1" aria-labelledby="recapExportModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <form method="GET" action="{{ route('bookings.recap.export') }}">
                    <div class="modal-header">
                        <h5 class="modal-title" id="recapExportModalLabel">{{ __('translation.export-booking-recap') }}</h5>
                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                    </div>
                    <div class="modal-body">
                        <input type="hidden" name="specific_date" value="{{ $filters['specific_date'] }}">
                        <input type="hidden" name="date_from" value="{{ $filters['date_from'] }}">
                        <input type="hidden" name="date_to" value="{{ $filters['date_to'] }}">
                        <input type="hidden" name="channel" value="{{ $filters['channel'] }}">

                        <div class="mb-3">
                            <label class="form-label">{{ __('translation.file-format') }}</label>
                            <select class="form-select" name="format">
                                <option value="csv">CSV</option>
                                <option value="excel">Excel (.xls)</option>
                            </select>
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Delimiter</label>
                            <select class="form-select" name="delimiter">
                                <option value="semicolon">Titik Koma (;)</option>
                                <option value="colon">Titik Dua (:)</option>
                            </select>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                        <button type="submit" class="btn btn-success">
                            <i class="ri-file-download-line align-bottom me-1"></i>{{ __('translation.download') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script src="{{ URL::asset('build/libs/apexcharts/apexcharts.min.js') }}"></script>
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            var trendData = @json(($trendDaily ?? collect())->map(fn($row) => [
                'date' => (string) ($row->trend_date ?? ''),
                'net_idr' => (float) ($row->net_idr ?? 0),
            ])->values()->all());
            var target = document.querySelector('#bookingRevenueTrendChart');
            if (!target) {
                return;
            }

            var labels = trendData.map(function (item) { return item.date; });
            var values = trendData.map(function (item) { return Math.round(item.net_idr); });

            var options = {
                chart: { type: 'line', height: 320, toolbar: { show: false } },
                stroke: { curve: 'smooth', width: 3 },
                series: [{ name: @json(__('translation.net-revenue')), data: values }],
                xaxis: { categories: labels },
                yaxis: {
                    labels: {
                        formatter: function (value) {
                            return 'IDR ' + Number(value || 0).toLocaleString('id-ID');
                        }
                    }
                },
                tooltip: {
                    y: {
                        formatter: function (value) {
                            return 'IDR ' + Number(value || 0).toLocaleString('id-ID');
                        }
                    }
                },
                noData: { text: @json(__('translation.no-trend-data-selected-filter')) },
                colors: ['#0ab39c']
            };

            new ApexCharts(target, options).render();
        });
    </script>
@endsection
