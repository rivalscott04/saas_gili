<!-- ========== App Menu ========== -->
<div class="app-menu navbar-menu">
    <!-- LOGO -->
    <div class="navbar-brand-box">
        <!-- Dark Logo-->
        <a href="{{ route('root') }}" class="logo logo-dark">
            <span class="logo-sm">
                <img src="{{ URL::asset('images/logo-dark.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('images/logo-dark.png') }}" alt="" height="50" width="185">
            </span>
        </a>
        <!-- Light Logo-->
        <a href="{{ route('root') }}" class="logo logo-light">
            <span class="logo-sm">
                <img src="{{ URL::asset('images/logo-light.png') }}" alt="" height="22">
            </span>
            <span class="logo-lg">
                <img src="{{ URL::asset('images/logo-light.png') }}" alt="" height="50" width="185">
            </span>
        </a>
        <button type="button" class="btn btn-sm p-0 fs-20 header-item float-end btn-vertical-sm-hover"
            id="vertical-hover">
            <i class="ri-record-circle-line"></i>
        </button>
    </div>

    <div id="scrollbar">
        <div class="container-fluid">

            <div id="two-column-menu">
            </div>
            @php
                $sidebarUser = auth()->user();
                $isSidebarSuperAdmin = $sidebarUser?->role === 'superadmin';
                $isSidebarAdmin = $sidebarUser?->isSuperAdmin() || $sidebarUser?->isTenantAdmin();
                // Onboarding nav flags: App\View\Composers\OnboardingComposer (singleton OnboardingService).
                $showOnboardingLink = $onboardingShowNavLink ?? false;
            @endphp
            <ul class="navbar-nav" id="navbar-nav">
                <li class="menu-title"><span>@lang('translation.menu')</span></li>
                @if ($showOnboardingLink)
                    {{-- Entry pertama untuk tenant_admin yang baru: shortcut ke setup checklist.
                         Sembunyi otomatis setelah 5/5 mandatory done atau user menekan
                         "Sembunyikan dari beranda". --}}
                    <li class="nav-item" data-sidebar="onboarding-entry">
                        <a class="nav-link menu-link" href="{{ route('onboarding.index') }}">
                            <i class="bx bx-rocket"></i>
                            <span>{{ __('translation.onboarding-checklist') }}</span>
                        </a>
                    </li>
                @endif
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarDashboards" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarDashboards">
                        <i class="bx bxs-dashboard"></i> <span>{{ $isSidebarAdmin ? __('translation.dashboard-reports') : __('translation.dashboards') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarDashboards">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('dashboard-analytics') }}" class="nav-link">@lang('translation.analytics')</a>
                            </li>
                        </ul>
                    </div>
                </li> <!-- end Dashboard Menu -->
                @if ($isSidebarAdmin)
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarOps" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarOps">
                        <i class="bx bx-briefcase-alt-2"></i> <span>{{ __('translation.operations-resources') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarOps">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ url('apps-bookings') }}" class="nav-link">{{ __('translation.bookings') }}</a>
                            </li>
                            {{-- Entry-point cepat untuk membuat booking manual (docs/ux-review/2026-05-14-tenant-navigation-review.md §2.6 + P1.7). Sebelumnya hanya tersedia sebagai tombol di dalam Booking List. --}}
                            <li class="nav-item">
                                <a href="{{ route('bookings.manual.create') }}" class="nav-link">{{ __('translation.create-manual-booking') }}</a>
                            </li>
                            {{-- Shortcut ke daftar permintaan reschedule. Hash dipakai supaya JS di Booking List bisa otomatis memilih tab Reschedule Request saat dibuka. --}}
                            <li class="nav-item">
                                <a href="{{ url('apps-bookings#reschedule_requested') }}" class="nav-link">{{ __('translation.reschedule-requests') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ url('apps-bookings-calendar') }}" class="nav-link">{{ __('translation.booking-calendar') }}</a>
                            </li>
                            {{-- "Sales Report (Booking Recap)" dipindah ke sini (group Booking/Operations) supaya konsisten dengan breadcrumb halaman "Tour Operations › Revenue Recap". --}}
                            <li class="nav-item">
                                <a href="{{ route('bookings.recap') }}" class="nav-link">{{ __('translation.sales-report-booking-recap') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tours.index') }}" class="nav-link">{{ __('translation.tour-management') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tour-day-capacities.index') }}" class="nav-link">{{ __('translation.tour-daily-capacity') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('operations-resources.index') }}" class="nav-link">{{ __('translation.resource-management') }}</a>
                            </li>
                            @if ($sidebarUser?->hasTenantPermission('whatsapp_templates.manage'))
                            <li class="nav-item">
                                <a href="{{ url('apps-whatsapp-template-message') }}" class="nav-link">{{ __('translation.whatsapp-template') }}</a>
                            </li>
                            @endif
                            @if ($sidebarUser?->hasTenantPermission('invoices.view'))
                            <li class="nav-item">
                                <a href="{{ route('tenant-invoices.index') }}" class="nav-link">@lang('translation.invoices')</a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>

                @canany(['viewAny'], [\App\Models\TravelAgent::class, \App\Models\ChannelSyncLog::class])
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarSalesChannels" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarSalesChannels">
                        <i class="ri-broadcast-line"></i> <span>{{ __('translation.sales-channels') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarSalesChannels">
                        <ul class="nav nav-sm flex-column">
                            @can('viewAny', \App\Models\TravelAgent::class)
                            <li class="nav-item">
                                <a href="{{ route('travel-agents.index') }}" class="nav-link">{{ __('translation.channel-connections') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('channel-sync.index') }}" class="nav-link">{{ __('translation.channel-sync') }}</a>
                            </li>
                            @endcan
                            @can('viewAny', \App\Models\ChannelSyncLog::class)
                            <li class="nav-item">
                                <a href="{{ route('channel-sync-logs.index') }}" class="nav-link">{{ __('translation.sync-logs') }}</a>
                            </li>
                            @endcan
                            @if (($sidebarTravelAgents ?? collect())->isNotEmpty())
                                <li class="menu-title mt-2"><span>{{ __('translation.sidebar-ota-channels') }}</span></li>
                                @foreach ($sidebarTravelAgents as $sidebarAgent)
                                    @php
                                        $sidebarLogo = $sidebarAgent['branding'] ?? [];
                                    @endphp
                                    <li class="nav-item">
                                        <a href="{{ route('travel-agents.index', ['agent' => $sidebarAgent['code']]) }}"
                                            class="nav-link d-flex align-items-center gap-2 py-2">
                                            <span class="flex-shrink-0 d-inline-flex align-items-center justify-content-center rounded-circle border overflow-hidden"
                                                style="width: 22px; height: 22px; border-color: {{ $sidebarLogo['brand_color'] ?? '#6C757D' }} !important;">
                                                @if (! empty($sidebarLogo['image']))
                                                    <img src="{{ $sidebarLogo['image'] }}" alt=""
                                                        width="16" height="16" style="object-fit: contain;"
                                                        onerror="this.style.display='none'; this.nextElementSibling.style.display='inline';">
                                                    <span class="fs-10 fw-semibold" style="display: none; color: {{ $sidebarLogo['brand_color'] ?? '#6C757D' }};">
                                                        {{ $sidebarLogo['label'] ?? '' }}
                                                    </span>
                                                @else
                                                    <span class="fs-10 fw-semibold" style="color: {{ $sidebarLogo['brand_color'] ?? '#6C757D' }};">
                                                        {{ $sidebarLogo['label'] ?? '' }}
                                                    </span>
                                                @endif
                                            </span>
                                            <span class="flex-grow-1 text-truncate">{{ $sidebarAgent['name'] }}</span>
                                        </a>
                                    </li>
                                @endforeach
                            @endif
                        </ul>
                    </div>
                </li>
                @endcanany

                @if ($isSidebarSuperAdmin)
                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarTenantManagement" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarTenantManagement">
                        <i class="bx bx-buildings"></i> <span>{{ __('translation.tenant-management') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarTenantManagement">
                        <ul class="nav nav-sm flex-column">
                            <li class="nav-item">
                                <a href="{{ route('superadmin.tenants.index') }}" class="nav-link">{{ __('translation.tenants') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-users.index') }}" class="nav-link">{{ __('translation.tenant-users') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-role-permissions.index') }}" class="nav-link">{{ __('translation.role-permissions') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-categories.index') }}" class="nav-link">{{ __('translation.tenant-categories') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-audit-logs.index') }}" class="nav-link">{{ __('translation.audit-logs') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('superadmin-landing-pricing.index') }}" class="nav-link">{{ __('translation.pricing') }}</a>
                            </li>
                            @if (\App\Support\SuperAdminImpersonation::isEnabled())
                            <li class="nav-item">
                                <a href="{{ route('superadmin.impersonation.index') }}" class="nav-link">{{ __('translation.superadmin-impersonate') }}</a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @endif

                <li class="nav-item">
                    <a class="nav-link menu-link" href="#sidebarAdminSettings" data-bs-toggle="collapse" role="button"
                        aria-expanded="false" aria-controls="sidebarAdminSettings">
                        <i class="bx bx-cog"></i> <span>{{ __('translation.customers-settings') }}</span>
                    </a>
                    <div class="collapse menu-dropdown" id="sidebarAdminSettings">
                        <ul class="nav nav-sm flex-column">
                            @if (! $isSidebarSuperAdmin)
                            <li class="nav-item">
                                <a href="{{ route('tenant-users.index') }}" class="nav-link">{{ __('translation.tenant-users') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-role-permissions.index') }}" class="nav-link">{{ __('translation.role-permissions') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-categories.index') }}" class="nav-link">{{ __('translation.tenant-categories') }}</a>
                            </li>
                            <li class="nav-item">
                                <a href="{{ route('tenant-audit-logs.index') }}" class="nav-link">{{ __('translation.audit-logs') }}</a>
                            </li>
                            @endif
                        </ul>
                    </div>
                </li>
                @else
                @include('layouts.partials.sidebar-guide-nav')
                @endif

            </ul>
        </div>
        <!-- Sidebar -->
    </div>
    <div class="sidebar-background"></div>
</div>
<!-- Left Sidebar End -->
<!-- Vertical Overlay-->
<div class="vertical-overlay"></div>
