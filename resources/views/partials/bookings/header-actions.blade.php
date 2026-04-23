<div class="card-header border-0">
    <div class="row align-items-center gy-3">
        <div class="col-sm">
            <h5 class="card-title mb-0">{{ __('translation.booking-management') }}</h5>
        </div>
        <div class="col-sm-auto">
            <div class="d-flex gap-1 flex-wrap">
                @can('create', \App\Models\Booking::class)
                    <a href="{{ route('bookings.manual.create') }}" class="btn btn-soft-success"><i class="ri-add-line align-bottom me-1"></i> {{ __('translation.create-booking') }}</a>
                @else
                    <span class="btn btn-soft-secondary disabled" title="{{ __('translation.no-booking-access') }}"><i class="ri-add-line align-bottom me-1"></i> {{ __('translation.create-booking') }}</span>
                @endcan
                <a href="{{ route('bookings.recap') }}" class="btn btn-primary"><i class="ri-bar-chart-box-line align-bottom me-1"></i> {{ __('translation.revenue-recap') }}</a>
                <a href="apps-bookings-calendar" class="btn btn-info"><i class="ri-calendar-2-line align-bottom me-1"></i> {{ __('translation.booking-calendar') }}</a>
            </div>
        </div>
    </div>
</div>
