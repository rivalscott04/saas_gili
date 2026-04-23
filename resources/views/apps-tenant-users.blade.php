@extends('layouts.master')

@section('title')
    {{ __('translation.tenant-users') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.tenant') }}
        @endslot
        @slot('title')
            {{ __('translation.user-management') }}
        @endslot
    @endcomponent

    <div id="tenantUsersAjaxContainer">
    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">{{ __('translation.tenant') }}</p>
                    @if ($showTenantSwitcher ?? false)
                        <label class="visually-hidden" for="tenantUserTenantSelect">{{ __('translation.choose-tenant') }}</label>
                        <select id="tenantUserTenantSelect" class="form-select form-select-sm">
                            @foreach ($availableTenants as $tenantOption)
                                <option value="{{ $tenantOption->code }}" {{ (int) $selectedTenantId === (int) $tenantOption->id ? 'selected' : '' }}>
                                    {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-muted small mb-0 mt-2">{{ __('translation.switch-tenant-help') }}</p>
                    @else
                        <h5 class="mb-0">{{ $tenant?->name ?? 'Tenant' }}</h5>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">{{ __('translation.seat-package') }}</p>
                    <h5 class="mb-0">{{ $totalUsers }}/{{ $maxUsers }} {{ __('translation.users-label') }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">{{ __('translation.remaining-seats') }}</p>
                    <h5 class="mb-0">{{ $remainingSeats }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">{{ __('translation.custom-roles') }}</p>
                    <h5 class="mb-0">{{ $customRolesCount }}/{{ $maxCustomRoles }}</h5>
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm" id="tenantUserList">
                <div class="card-header bg-white border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1">{{ __('translation.tenant-user-list') }}</h5>
                            <p class="text-muted mb-0">{{ __('translation.tenant-user-list-help') }}</p>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-soft-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenantRoleModal">
                                <i class="ri-shield-user-line align-bottom me-1"></i> {{ __('translation.add-role') }}
                            </button>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenantUserModal">
                                <i class="ri-user-add-line align-bottom me-1"></i> {{ __('translation.add-user') }}
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>{{ __('translation.name') }}</th>
                                    <th>{{ __('translation.email') }}</th>
                                    <th>{{ __('translation.role') }}</th>
                                    <th>{{ __('translation.status') }}</th>
                                    <th>{{ __('translation.created-at') }}</th>
                                    <th class="text-end">{{ __('translation.action') }}</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($users as $item)
                                    @php
                                        $status = strtolower((string) ($item->status ?? 'active'));
                                        $isActive = $status !== 'suspended';
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $item->name }}</td>
                                        <td>{{ $item->email }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ str($item->role)->replace('_', ' ')->title() }}</span></td>
                                        <td>
                                            @if ($isActive)
                                                <span class="badge bg-success-subtle text-success">{{ __('translation.active') }}</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">{{ __('translation.suspended') }}</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($item->created_at)->format('d M Y H:i') }}</td>
                                        <td class="text-end">
                                            <form method="POST" action="{{ route('tenant-users.update-status', $item) }}" class="d-inline">
                                                @csrf
                                                @if ($showTenantSwitcher ?? false)
                                                    <input type="hidden" name="tenant_code" value="{{ $tenant?->code }}">
                                                @endif
                                                <input type="hidden" name="status" value="{{ $isActive ? 'suspended' : 'active' }}">
                                                <button class="btn btn-sm {{ $isActive ? 'btn-soft-danger' : 'btn-soft-success' }} js-tenant-user-status-confirm"
                                                    type="submit"
                                                    data-confirm-title="{{ $isActive ? __('translation.confirm-suspend-user-title') : __('translation.confirm-activate-user-title') }}"
                                                    data-confirm-text="{{ $isActive ? __('translation.confirm-suspend-user-text') : __('translation.confirm-activate-user-text') }}"
                                                    data-confirm-button="{{ $isActive ? __('translation.confirm-suspend-user-button') : __('translation.confirm-activate-user-button') }}">
                                                    {{ $isActive ? __('translation.suspend') : __('translation.activate') }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-user-search-line fs-2 d-block mb-2"></i>
                                                {{ __('translation.no-tenant-users-yet') }}
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($users->count() > 0)
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                {{ __('translation.showing-range-of-total-users', ['from' => $users->firstItem(), 'to' => $users->lastItem(), 'total' => $users->total()]) }}
                            </small>
                            {{ $users->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantUserModal" tabindex="-1" aria-labelledby="createTenantUserModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-lg modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <div>
                        <h5 class="modal-title" id="createTenantUserModalLabel">{{ __('translation.add-tenant-user') }}</h5>
                        <p class="text-muted mb-0">{{ __('translation.create-account-and-role-help') }}</p>
                    </div>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tenant-users.store') }}">
                    @csrf
                    @if ($showTenantSwitcher ?? false)
                        <input type="hidden" name="tenant_code" value="{{ $tenant?->code }}">
                    @endif
                    <div class="modal-body">
                        <div class="row g-3">
                            <div class="col-md-6">
                                <label class="form-label">{{ __('translation.full-name') }}</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" autocomplete="name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('translation.email') }}</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" placeholder="nama@perusahaan.com" autocomplete="email" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('translation.role') }}</label>
                                <select id="roleSelect" name="role" class="form-select @error('role') is-invalid @enderror" required>
                                    @foreach ($roles as $roleItem)
                                        <option value="{{ $roleItem->code }}" {{ old('role') === $roleItem->code ? 'selected' : '' }}>{{ $roleItem->name }}</option>
                                    @endforeach
                                </select>
                                @error('role')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">{{ __('translation.password') }}</label>
                                <div class="input-group">
                                    <input id="passwordInput" type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        value="{{ old('password') }}" minlength="8"
                                        placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                                    <button class="btn btn-soft-primary" type="button" id="togglePasswordBtn"
                                        aria-label="{{ __('translation.toggle-password-visibility') }}">{{ __('translation.show') }}</button>
                                    <button class="btn btn-soft-info" type="button" id="generatePasswordBtn">{{ __('translation.generate') }}</button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-user-add-line align-bottom me-1"></i> {{ __('translation.save-user') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <div class="modal fade" id="createTenantRoleModal" tabindex="-1" aria-labelledby="createTenantRoleModalLabel" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content border-0 shadow">
                <div class="modal-header">
                    <h5 class="modal-title" id="createTenantRoleModalLabel">{{ __('translation.add-custom-role') }}</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tenant-users.store-role') }}">
                    @csrf
                    @if ($showTenantSwitcher ?? false)
                        <input type="hidden" name="tenant_code" value="{{ $tenant?->code }}">
                    @endif
                    <div class="modal-body">
                        <div class="alert alert-info">
                            {{ __('translation.custom-role-limit-info') }} <strong>{{ $customRolesCount }}/{{ $maxCustomRoles }}</strong>.
                        </div>
                        <div class="mb-0">
                            <label class="form-label">{{ __('translation.role-name') }}</label>
                            <input type="text" name="role_name" class="form-control @error('role_name') is-invalid @enderror" maxlength="120"
                                placeholder="Contoh: Sales Coordinator" required>
                            @error('role_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">{{ __('translation.after-role-created-help') }}</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">{{ __('translation.cancel') }}</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> {{ __('translation.save-role') }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>
    </div>

    <script>
        window.initTenantUsersPage = function() {
            const passwordInput = document.getElementById('passwordInput');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const generatePasswordBtn = document.getElementById('generatePasswordBtn');
            const userModalElement = document.getElementById('createTenantUserModal');
            const roleModalElement = document.getElementById('createTenantRoleModal');
            const tenantSwitcher = document.getElementById('tenantUserTenantSelect');
            const ajaxContainer = document.getElementById('tenantUsersAjaxContainer');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    togglePasswordBtn.textContent = isHidden ? @json(__('translation.hide')) : @json(__('translation.show'));
                });
            }

            if (generatePasswordBtn && passwordInput) {
                generatePasswordBtn.addEventListener('click', function() {
                    const chars = 'ABCDEFGHJKLMNPQRSTUVWXYZabcdefghijkmnopqrstuvwxyz23456789!@#$%';
                    let generated = '';
                    for (let i = 0; i < 12; i++) {
                        generated += chars.charAt(Math.floor(Math.random() * chars.length));
                    }
                    passwordInput.value = generated;
                    passwordInput.type = 'text';
                    if (togglePasswordBtn) {
                        togglePasswordBtn.textContent = @json(__('translation.hide'));
                    }
                });
            }

            @if ($errors->has('name') || $errors->has('email') || $errors->has('role') || $errors->has('password'))
                if (userModalElement && window.bootstrap) {
                    const userModal = new bootstrap.Modal(userModalElement);
                    userModal.show();
                }
            @endif

            @if ($errors->has('role_name'))
                if (roleModalElement && window.bootstrap) {
                    const roleModal = new bootstrap.Modal(roleModalElement);
                    roleModal.show();
                }
            @endif

            const statusButtons = document.querySelectorAll('.js-tenant-user-status-confirm');
            statusButtons.forEach(function(button) {
                if (button.dataset.confirmBound) {
                    return;
                }
                button.dataset.confirmBound = '1';
                button.addEventListener('click', function(event) {
                    event.preventDefault();
                    const form = button.closest('form');
                    if (!form || typeof Swal === 'undefined') {
                        if (form) {
                            form.submit();
                        }
                        return;
                    }

                    Swal.fire({
                        title: button.getAttribute('data-confirm-title') || @json(__('translation.are-you-sure')),
                        text: button.getAttribute('data-confirm-text') || '',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: button.getAttribute('data-confirm-button') || 'Ya',
                        cancelButtonText: @json(__('translation.cancel')),
                        customClass: {
                            confirmButton: 'btn btn-primary w-xs me-2 mt-2',
                            cancelButton: 'btn btn-light w-xs mt-2',
                        },
                        buttonsStyling: false,
                        showCloseButton: true
                    }).then(function(result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            if (tenantSwitcher && ajaxContainer && !tenantSwitcher.dataset.ajaxBound) {
                tenantSwitcher.dataset.ajaxBound = '1';
                tenantSwitcher.addEventListener('change', function() {
                    const nextUrl = "{{ route('tenant-users.index') }}?tenant=" + encodeURIComponent(this.value);
                    tenantSwitcher.disabled = true;
                    ajaxContainer.classList.add('opacity-75');

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load tenant users page.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            const doc = new DOMParser().parseFromString(html, 'text/html');
                            const freshContainer = doc.getElementById('tenantUsersAjaxContainer');
                            if (!freshContainer) {
                                throw new Error('Missing refreshed tenant users content.');
                            }
                            ajaxContainer.outerHTML = freshContainer.outerHTML;
                            history.pushState({}, '', nextUrl);
                            if (typeof window.initTenantUsersPage === 'function') {
                                window.initTenantUsersPage();
                            }
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            tenantSwitcher.disabled = false;
                            ajaxContainer.classList.remove('opacity-75');
                        });
                });
            }
        };

        document.addEventListener('DOMContentLoaded', function() {
            if (typeof window.initTenantUsersPage === 'function') {
                window.initTenantUsersPage();
            }
        });
    </script>
@endsection
