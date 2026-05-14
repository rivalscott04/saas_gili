@php
    $topbarUser = auth()->user();
    $isTopbarSuperAdmin = $topbarUser?->isSuperAdmin() ?? false;
    $isTopbarTenantAdmin = $topbarUser?->isTenantAdmin() ?? false;
    $topbarTenant = $topbarUser && ! $isTopbarSuperAdmin
        ? optional($topbarUser->tenant)
        : null;
    $topbarRoleCode = strtolower((string) ($topbarUser->role ?? ''));
    $topbarRoleLabels = [
        'superadmin' => __('translation.role-label-superadmin'),
        'tenant_admin' => __('translation.role-label-tenant-admin'),
        'operator' => __('translation.role-label-operator'),
        'guide' => __('translation.role-label-guide'),
    ];
    $topbarRoleLabel = $topbarRoleLabels[$topbarRoleCode] ?? ($topbarRoleCode !== ''
        ? \Illuminate\Support\Str::headline(str_replace('_', ' ', $topbarRoleCode))
        : __('translation.role-label-member'));
@endphp
<header id="page-topbar">
    <div class="layout-width">
        <div class="navbar-header">
            <div class="d-flex">
                <!-- LOGO -->
                <div class="navbar-brand-box horizontal-logo">
                    <a href="{{ route('root') }}" class="logo logo-dark">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('images/logo-dark.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('images/logo-dark.png') }}" alt="" height="17">
                        </span>
                    </a>

                    <a href="{{ route('root') }}" class="logo logo-light">
                        <span class="logo-sm">
                            <img src="{{ URL::asset('images/logo-light.png') }}" alt="" height="22">
                        </span>
                        <span class="logo-lg">
                            <img src="{{ URL::asset('images/logo-light.png') }}" alt="" height="17">
                        </span>
                    </a>
                </div>

                <button type="button" class="btn btn-sm px-3 fs-16 header-item vertical-menu-btn topnav-hamburger" id="topnav-hamburger-icon">
                    <span class="hamburger-icon">
                        <span></span>
                        <span></span>
                        <span></span>
                    </span>
                </button>

                @if ($topbarUser && ! $isTopbarSuperAdmin && $topbarTenant && $topbarTenant->name)
                    <div class="d-none d-md-flex align-items-center ms-2">
                        <span class="badge bg-primary-subtle text-primary fs-12">
                            <i class="ri-store-2-line align-bottom me-1"></i>{{ $topbarTenant->name }}
                        </span>
                    </div>
                @endif
            </div>

            <div class="d-flex align-items-center">

                <div class="dropdown ms-1 topbar-head-dropdown header-item">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        @switch(Session::get('lang'))
                        @case('id')
                        <img src="{{ URL::asset('build/images/flags/id.svg') }}" class="me-2 rounded" height="20" alt="Header Language">
                        @break
                        @default
                        <img src="{{ URL::asset('build/images/flags/us.svg') }}" class="me-2 rounded" height="20" alt="Header Language" height="16">
                        @endswitch
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <a href="{{ url('index/en') }}" class="dropdown-item notify-item language py-2" data-lang="en" title="English">
                            <img src="{{ URL::asset('build/images/flags/us.svg') }}" alt="user-image" class="me-2 rounded" height="20">
                            <span class="align-middle">English</span>
                        </a>
                        <a href="{{ url('index/id') }}" class="dropdown-item notify-item language py-2" data-lang="id" title="Bahasa Indonesia">
                            <img src="{{ URL::asset('build/images/flags/id.svg') }}" alt="user-image" class="me-2 rounded" height="20">
                            <span class="align-middle">Bahasa Indonesia</span>
                        </a>
                    </div>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle" data-toggle="fullscreen">
                        <i class='bx bx-fullscreen fs-22'></i>
                    </button>
                </div>

                <div class="ms-1 header-item d-none d-sm-flex">
                    <button type="button" class="btn btn-icon btn-topbar btn-ghost-secondary rounded-circle light-dark-mode">
                        <i class='bx bx-moon fs-22'></i>
                    </button>
                </div>

                <div class="dropdown ms-sm-3 header-item topbar-user">
                    <button type="button" class="btn" id="page-header-user-dropdown" data-bs-toggle="dropdown" aria-haspopup="true" aria-expanded="false">
                        <span class="d-flex align-items-center">
                            <img class="rounded-circle header-profile-user" src="@if (Auth::user()->avatar != ''){{ URL::asset('images/' . Auth::user()->avatar) }}@else{{ URL::asset('build/images/users/avatar-1.jpg') }}@endif" alt="Header Avatar">
                            <span class="text-start ms-xl-2">
                                <span class="d-none d-xl-inline-block ms-1 fw-medium user-name-text">{{ Auth::user()->name }}</span>
                                <span class="d-none d-xl-block ms-1 fs-12 user-name-sub-text">
                                    @if ($isTopbarSuperAdmin)
                                        {{ $topbarRoleLabel }}
                                    @elseif ($topbarTenant && $topbarTenant->name)
                                        {{ $topbarRoleLabel }} · {{ $topbarTenant->name }}
                                    @else
                                        {{ $topbarRoleLabel }}
                                    @endif
                                </span>
                            </span>
                        </span>
                    </button>
                    <div class="dropdown-menu dropdown-menu-end">
                        <h6 class="dropdown-header">
                            {{ __('translation.signed-in-as', ['name' => Auth::user()->name]) }}
                        </h6>
                        <div class="px-3 pb-2">
                            <div class="small text-muted">{{ __('translation.role') }}</div>
                            <div class="fw-semibold">{{ $topbarRoleLabel }}</div>
                            @if (! $isTopbarSuperAdmin && $topbarTenant && $topbarTenant->name)
                                <div class="small text-muted mt-2">{{ __('translation.tenant') }}</div>
                                <div class="fw-semibold">{{ $topbarTenant->name }}</div>
                            @endif
                        </div>
                        <div class="dropdown-divider"></div>
                        <a class="dropdown-item" href="{{ url('pages-profile') }}"><i class="mdi mdi-account-circle text-muted fs-16 align-middle me-1"></i> <span class="align-middle">{{ __('translation.profile') }}</span></a>
                        <a class="dropdown-item " href="javascript:void();" onclick="event.preventDefault(); document.getElementById('logout-form').submit();"><i class="bx bx-power-off font-size-16 align-middle me-1"></i> <span key="t-logout">@lang('translation.logout')</span></a>
                        <form id="logout-form" action="{{ route('logout') }}" method="POST" style="display: none;">
                            @csrf
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</header>
