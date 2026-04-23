@extends('layouts.master')
@section('title')
{{ __('translation.booking-list') }}
@endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
{{ __('translation.tour-operations') }}
@endslot
@slot('title')
{{ __('translation.booking-list') }}
@endslot
@endcomponent

<div class="row">
    <div class="col-lg-12">
        <div class="card">
            @include('partials.bookings.header-actions')
            <div class="card-body pt-0">
                @include('partials.bookings.status-filters')
                @include('partials.bookings.table')
                @include('partials.bookings.modals')
            </div>
        </div>
    </div>
</div>
@endsection
@section('script')
<script src="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.js') }}"></script>
@include('partials.bookings.scripts')
@endsection
