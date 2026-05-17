@extends('layouts.master')
@section('title')
{{ __('translation.booking-list') }}
@endsection
@section('css')
<link href="{{ URL::asset('build/libs/sweetalert2/sweetalert2.min.css') }}" rel="stylesheet" type="text/css" />
{{-- Shepherd.js style untuk onboarding tour (docs/ux-review/2026-05-14-tenant-onboarding-plan.md Phase E). --}}
<link href="{{ URL::asset('build/libs/shepherd.js/css/shepherd.css') }}" rel="stylesheet" type="text/css" />
@endsection
@section('content')
@component('components.breadcrumb')
@slot('li_1')
{{-- Breadcrumb diselaraskan dengan group sidebar "Operations & Resources" (docs/ux-review/2026-05-14-tenant-navigation-review.md §2.4). --}}
{{ __('translation.operations-resources') }}
@endslot
@slot('title')
{{ __('translation.booking-list') }}
@endslot
@endcomponent

@if ($showTwoWaySyncInactiveBanner ?? false)
    <div class="row mb-3">
        <div class="col-12">
            <x-onboarding.empty-state
                icon="bx-link-external"
                tone="warning"
                :title="__('translation.empty-state-two-way-sync-inactive-title')"
                :description="__('translation.empty-state-two-way-sync-inactive-desc')"
                :actions="[
                    ['label' => __('translation.empty-state-two-way-sync-inactive-cta'), 'href' => route('travel-agents.index'), 'variant' => 'primary'],
                ]"
            />
        </div>
    </div>
@endif

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
@include('partials.onboarding.page-tour-config', [
    'pageId' => 'bookings-list',
    'steps' => [
        ['target' => 'create-booking-btn', 'title' => __('translation.onboarding-tour-bookings-create-title'), 'text' => __('translation.onboarding-tour-bookings-create-text'), 'on' => 'bottom'],
        ['target' => 'booking-status-tabs', 'title' => __('translation.onboarding-tour-bookings-status-title'), 'text' => __('translation.onboarding-tour-bookings-status-text'), 'on' => 'bottom'],
        ['target' => 'booking-workflow-tabs', 'title' => __('translation.onboarding-tour-bookings-workflow-title'), 'text' => __('translation.onboarding-tour-bookings-workflow-text'), 'on' => 'bottom'],
        ['target' => 'booking-source-badge', 'title' => __('translation.onboarding-tour-bookings-channel-title'), 'text' => __('translation.onboarding-tour-bookings-channel-text'), 'on' => 'right'],
        ['target' => 'send-magic-link-btn', 'title' => __('translation.onboarding-tour-bookings-magic-link-title'), 'text' => __('translation.onboarding-tour-bookings-magic-link-text'), 'on' => 'left'],
    ],
])
{{-- Shepherd.js tour onboarding (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §5.1 + Phase E). --}}
<script src="{{ URL::asset('build/libs/shepherd.js/js/shepherd.min.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/onboarding/_tour-helper.js') }}"></script>
<script src="{{ URL::asset('build/js/pages/onboarding/bookings-list.tour.js') }}"></script>
@endsection
