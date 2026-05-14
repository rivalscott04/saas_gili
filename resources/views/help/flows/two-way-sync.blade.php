{{--
    Drawer alur "Sync 2 arah dengan OTA" (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §9).
    Wajib jujur: inbound sudah jalan, outbound saat ini masih perlu di-dispatch
    secara manual via tombol GYG-sync (lihat §13.6) — sampai auto-dispatch aktif.
--}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="helpFlowTwoWaySync" aria-labelledby="helpFlowTwoWaySyncLabel" data-help-flow-drawer="two-way-sync">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-1" id="helpFlowTwoWaySyncLabel">
                {{ __('translation.help-flow-two-way-sync') }}
            </h5>
            <p class="text-muted small mb-0">{{ __('translation.help-flow-two-way-sync-subtitle') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4">
            <h6 class="text-uppercase text-muted small">{{ __('translation.help-flow-two-way-sync-inbound-title') }}</h6>
            <p class="mb-0">{{ __('translation.help-flow-two-way-sync-inbound-desc') }}</p>
        </div>
        <div class="mb-4">
            <h6 class="text-uppercase text-muted small">{{ __('translation.help-flow-two-way-sync-outbound-title') }}</h6>
            <p class="mb-0">{{ __('translation.help-flow-two-way-sync-outbound-desc') }}</p>
        </div>
        <div class="mb-4">
            <h6 class="text-uppercase text-muted small">{{ __('translation.help-flow-two-way-sync-status-title') }}</h6>
            <p class="mb-0">{{ __('translation.help-flow-two-way-sync-status-desc') }}</p>
        </div>
        <div class="alert alert-warning" role="alert">
            <i class="bx bx-error-circle me-1"></i>
            {{ __('translation.help-flow-two-way-sync-conflict-note') }}
        </div>
    </div>
</div>
