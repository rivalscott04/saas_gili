{{--
    Drawer alur "Buat booking di aplikasi" (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §9).
    Wajib menyebut: setelah Save booking otomatis di-push ke OTA terhubung (mode two_way_sync).
--}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="helpFlowAppBooking" aria-labelledby="helpFlowAppBookingLabel" data-help-flow-drawer="app-booking">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-1" id="helpFlowAppBookingLabel">
                {{ __('translation.help-flow-app-booking') }}
            </h5>
            <p class="text-muted small mb-0">{{ __('translation.help-flow-app-booking-subtitle') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <ol class="ps-3 mb-4">
            <li class="mb-3">
                <strong>{{ __('translation.help-flow-app-booking-step-1-title') }}</strong>
                <div class="text-muted small">{{ __('translation.help-flow-app-booking-step-1-desc') }}</div>
                <a href="{{ route('bookings.manual.create') }}" class="btn btn-sm btn-light mt-2">
                    {{ __('translation.help-flow-app-booking-step-1-cta') }}
                </a>
            </li>
            <li class="mb-3">
                <strong>{{ __('translation.help-flow-app-booking-step-2-title') }}</strong>
                <div class="text-muted small">{{ __('translation.help-flow-app-booking-step-2-desc') }}</div>
            </li>
            <li class="mb-3">
                <strong>{{ __('translation.help-flow-app-booking-step-3-title') }}</strong>
                <div class="text-muted small">{{ __('translation.help-flow-app-booking-step-3-desc') }}</div>
            </li>
        </ol>
        <div class="alert alert-info" role="alert">
            <i class="bx bx-info-circle me-1"></i>
            {{ __('translation.help-flow-app-booking-outbound-note') }}
        </div>
    </div>
</div>
