{{--
    Tombol bantuan global `?` di topbar (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §9).
    Tampil untuk semua user terautentikasi — superadmin pun bisa pakai untuk lihat
    alur produk saat mendukung tenant.

    Dropdown berisi:
    - Shortcut ke /onboarding
    - 4 flow drawer (magic link, sync 2 arah, booking di aplikasi, resource & kapasitas)
    - "Putar ulang panduan halaman ini" — emit JS event `onboarding:replay-tour`
      yang akan di-listen oleh tour script Shepherd per halaman (Phase E).
--}}
@auth
    <div class="dropdown ms-1 topbar-head-dropdown header-item" data-onboarding="help-menu">
        <button
            type="button"
            class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle"
            data-bs-toggle="dropdown"
            aria-haspopup="true"
            aria-expanded="false"
            aria-label="{{ __('translation.help-menu') }}"
        >
            <i class="bx bx-help-circle fs-22"></i>
        </button>
        <div class="dropdown-menu dropdown-menu-end" style="min-width: 18rem;">
            <h6 class="dropdown-header">{{ __('translation.help-menu') }}</h6>

            <a class="dropdown-item" href="{{ route('onboarding.index') }}" data-help-action="open-setup-checklist">
                <i class="bx bx-rocket me-2 text-primary"></i>
                {{ __('translation.help-open-setup-checklist') }}
            </a>

            <div class="dropdown-divider"></div>

            <button
                type="button"
                class="dropdown-item"
                data-bs-toggle="offcanvas"
                data-bs-target="#helpFlowMagicLink"
                data-help-flow="magic-link"
            >
                <i class="bx bx-message-rounded-check me-2 text-success"></i>
                {{ __('translation.help-flow-magic-link') }}
            </button>

            <button
                type="button"
                class="dropdown-item"
                data-bs-toggle="offcanvas"
                data-bs-target="#helpFlowTwoWaySync"
                data-help-flow="two-way-sync"
            >
                <i class="bx bx-transfer me-2 text-info"></i>
                {{ __('translation.help-flow-two-way-sync') }}
            </button>

            <button
                type="button"
                class="dropdown-item"
                data-bs-toggle="offcanvas"
                data-bs-target="#helpFlowAppBooking"
                data-help-flow="app-booking"
            >
                <i class="bx bx-edit-alt me-2 text-warning"></i>
                {{ __('translation.help-flow-app-booking') }}
            </button>

            <button
                type="button"
                class="dropdown-item"
                data-bs-toggle="offcanvas"
                data-bs-target="#helpFlowResourceCapacity"
                data-help-flow="resource-capacity"
            >
                <i class="bx bx-package me-2 text-secondary"></i>
                {{ __('translation.help-flow-resource-capacity') }}
            </button>

            <div class="dropdown-divider"></div>

            <button
                type="button"
                class="dropdown-item"
                data-help-action="replay-tour"
                onclick="window.dispatchEvent(new CustomEvent('onboarding:replay-tour'));"
            >
                <i class="bx bx-rotate-right me-2 text-primary"></i>
                {{ __('translation.help-replay-tour') }}
            </button>
        </div>
    </div>

    @include('help.flows.magic-link')
    @include('help.flows.two-way-sync')
    @include('help.flows.app-booking')
    @include('help.flows.resource-and-capacity')
@endauth
