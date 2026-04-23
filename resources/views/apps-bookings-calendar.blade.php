@extends('layouts.master')
@section('title')
Booking Calendar
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
Tour Operations
@endslot
@slot('title')
Booking Calendar
@endslot
@endcomponent

<div class="row">
    <div class="col-xl-3">
        <div class="card card-h-100">
            <div class="card-body">
                <h5 class="card-title mb-3">Status Legend</h5>
                <div class="d-flex flex-column gap-2">
                    <div><i class="mdi mdi-checkbox-blank-circle text-success me-2"></i>Confirmed</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-info me-2"></i>On Tour</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-secondary me-2"></i>Standby</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-warning me-2"></i>Pending</div>
                    <div><i class="mdi mdi-checkbox-blank-circle text-danger me-2"></i>Cancelled</div>
                </div>
                <hr>
                <h5 class="card-title mb-3">Source Legend</h5>
                <div class="d-flex flex-column gap-2">
                    <div><span class="badge bg-primary-subtle text-primary">MANUAL</span> Input manual internal</div>
                    <div><span class="badge bg-info-subtle text-info">OTA</span> Booking dari channel/agent</div>
                </div>
                <hr>
                <p class="text-muted mb-0">
                    Hover event di kalender untuk lihat detail peserta, paket tour, guide, dan titik jemput.
                </p>
            </div>
        </div>
        <div>
            <h5 class="mb-1">Upcoming Departures</h5>
            <p class="text-muted">Jadwal terdekat yang perlu disiapkan</p>
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
<script>
    window.bookingCalendarEvents = @json($bookingCalendarEvents ?? []);
</script>
<script src="{{ URL::asset('build/js/pages/booking-calendar.init.js') }}"></script>
@endsection
