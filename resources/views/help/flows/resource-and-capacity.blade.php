{{--
    Drawer alur "Kelola resource & kapasitas" (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §9).
    Wajib menegaskan resource bersifat lokal — tidak ikut sync ke OTA — supaya
    tenant tidak salah sangka armadanya muncul di OTA.
--}}
<div class="offcanvas offcanvas-end" tabindex="-1" id="helpFlowResourceCapacity" aria-labelledby="helpFlowResourceCapacityLabel" data-help-flow-drawer="resource-capacity">
    <div class="offcanvas-header border-bottom">
        <div>
            <h5 class="offcanvas-title mb-1" id="helpFlowResourceCapacityLabel">
                {{ __('translation.help-flow-resource-capacity') }}
            </h5>
            <p class="text-muted small mb-0">{{ __('translation.help-flow-resource-capacity-subtitle') }}</p>
        </div>
        <button type="button" class="btn-close" data-bs-dismiss="offcanvas" aria-label="Close"></button>
    </div>
    <div class="offcanvas-body">
        <div class="mb-4">
            <h6 class="text-uppercase text-muted small">{{ __('translation.help-flow-resource-capacity-resource-title') }}</h6>
            <p class="mb-2">{{ __('translation.help-flow-resource-capacity-resource-desc') }}</p>
            <a href="{{ route('operations-resources.index') }}" class="btn btn-sm btn-light">
                {{ __('translation.help-flow-resource-capacity-resource-cta') }}
            </a>
        </div>
        <div class="mb-4">
            <h6 class="text-uppercase text-muted small">{{ __('translation.help-flow-resource-capacity-quota-title') }}</h6>
            <p class="mb-2">{{ __('translation.help-flow-resource-capacity-quota-desc') }}</p>
            <a href="{{ route('tour-day-capacities.index') }}" class="btn btn-sm btn-light">
                {{ __('translation.help-flow-resource-capacity-quota-cta') }}
            </a>
        </div>
        <div class="alert alert-warning" role="alert">
            <i class="bx bx-info-circle me-1"></i>
            {{ __('translation.help-flow-resource-capacity-local-only-note') }}
        </div>
    </div>
</div>
