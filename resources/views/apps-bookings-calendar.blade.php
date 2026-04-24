@extends('layouts.master')
@section('title')
{{ __('translation.booking-calendar') }}
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
{{ __('translation.tour-operations') }}
@endslot
@slot('title')
{{ __('translation.booking-calendar') }}
@endslot
@endcomponent

<div class="row">
    <div class="col-xl-3">
        <div class="card card-h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">{{ __('translation.status-legend') }}</h5>
                <div class="d-flex flex-column gap-2">
                    <div><i class="mdi mdi-checkbox-blank-circle text-success me-2"></i>{{ __('translation.confirmed') }}</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-info me-2"></i>{{ __('translation.on-tour') }}</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-secondary me-2"></i>{{ __('translation.standby') }}</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-warning me-2"></i>{{ __('translation.pending') }}</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-danger me-2"></i>{{ __('translation.cancelled') }}</div>
                </div>
                <hr>
                <h5 class="card-title mb-3">{{ __('translation.source-legend') }}</h5>
                <div class="d-flex flex-column gap-2">
                    <div><span class="badge bg-primary-subtle text-primary">MANUAL</span> {{ __('translation.source-legend-manual-help') }}</div>
                    <div><span class="badge bg-info-subtle text-info">OTA</span> {{ __('translation.source-legend-ota-help') }}</div>
                </div>
                <hr>
                <p class="text-muted mb-0">
                    {{ __('translation.booking-calendar-hover-help') }}
                </p>
            </div>
        </div>
        <div>
            <h5 class="mb-1">{{ __('translation.upcoming-departures') }}</h5>
            <p class="text-muted">{{ __('translation.upcoming-departures-help') }}</p>
            <div class="pe-2 me-n1 mb-3" data-simplebar style="height: 400px">
                <div id="upcoming-booking-list"></div>
            </div>
        </div>
    </div>

    <div class="col-xl-9">
        <div class="card card-h-100">
            <div class="card-body">
                <div id="booking-calendar"></div>
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/fullcalendar/index.global.min.js') }}"></script>
@php
    $bookingCalendarI18n = [
        'locale' => str_replace('_', '-', app()->getLocale()),
        'guest' => __('translation.guest'),
        'package' => __('translation.tour-package'),
        'guide' => __('translation.guide'),
        'pickup' => __('translation.meeting-pickup'),
        'status' => __('translation.status'),
        'source' => __('translation.source'),
        'notes' => __('translation.notes'),
        'pax' => __('translation.pax'),
        'unknown' => __('translation.unknown'),
        'manual' => 'MANUAL',
        'ota' => 'OTA',
        'statusLabels' => [
            'confirmed' => __('translation.confirmed'),
            'on_tour' => __('translation.on-tour'),
            'standby' => __('translation.standby'),
            'pending' => __('translation.pending'),
            'cancelled' => __('translation.cancelled'),
        ],
    ];
@endphp
<script>
    window.bookingCalendarEvents = @json($bookingCalendarEvents ?? []);
    window.bookingCalendarI18n = @json($bookingCalendarI18n);
</script>
<script src="{{ URL::asset('build/js/pages/booking-calendar.init.js') }}"></script>
@endsection
