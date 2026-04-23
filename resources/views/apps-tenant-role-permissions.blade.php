@extends('layouts.master')

@section('title')
    {{ __('translation.role-permissions') }}
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            {{ __('translation.tenant') }}
        @endslot
        @slot('title')
            {{ __('translation.role-permissions') }}
        @endslot
    @endcomponent

    <div class="row" id="tenantRolePermissionsAjaxContainer">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">{{ __('translation.dynamic-role-permissions') }}</h5>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary">{{ $tenant?->name ?? __('translation.tenant') }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tenant-role-permissions.update') }}">
                        @csrf
                        <div class="row mb-4">
                            @if (auth()->user()?->isSuperAdmin())
                                <div class="col-lg-4">
                                    <label class="form-label">{{ __('translation.tenant') }}</label>
                                    <select class="form-select" id="rolePermissionTenantSwitcher">
                                        @foreach ($availableTenants as $tenantOption)
                                            <option value="{{ $tenantOption->code }}" {{ ($tenant?->code ?? '') === (string) $tenantOption->code ? 'selected' : '' }}>
                                                {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-4">
                                <label class="form-label">{{ __('translation.role') }}</label>
                                <select name="role" class="form-select" id="rolePermissionRoleSwitcher">
                                    @foreach ($roles as $role)
                                        <option value="{{ $role->code }}" {{ $selectedRole === $role->code ? 'selected' : '' }}>
                                            {{ $role->name }} ({{ $role->code }})
                                        </option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                        <div class="table-responsive table-card mt-2">
                            <table class="table align-middle table-nowrap mb-0">
                                <thead class="table-light text-muted text-uppercase">
                                    <tr>
                                        <th>{{ __('translation.access') }}</th>
                                        <th>{{ __('translation.description') }}</th>
                                        <th class="text-end">{{ __('translation.allow') }}</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permissions as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item['label'] }}</td>
                                            <td>{{ __('translation.permission-for-label', ['label' => strtolower($item['label'])]) }}</td>
                                            <td class="text-end">
                                                <div class="form-check form-switch d-inline-flex justify-content-end">
                                                    <input class="form-check-input" type="checkbox" name="permissions[]"
                                                        id="permission_{{ str_replace('.', '_', $item['key']) }}"
                                                        value="{{ $item['key'] }}" {{ $item['is_allowed'] ? 'checked' : '' }}>
                                                </div>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>

                        @error('permissions.*')
                            <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                        @enderror
                        @if (auth()->user()?->isSuperAdmin())
                            <input type="hidden" name="tenant_code" value="{{ $tenant?->code }}">
                        @endif
                        <div class="d-flex justify-content-end mt-4">
                            <button type="submit" class="btn btn-primary">
                                <i class="ri-save-line align-bottom me-1"></i> {{ __('translation.save-permissions') }}
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var initRolePermissionAjax = function() {
                var tenantSwitcher = document.getElementById('rolePermissionTenantSwitcher');
                var roleSwitcher = document.getElementById('rolePermissionRoleSwitcher');
                var container = document.getElementById('tenantRolePermissionsAjaxContainer');
                var baseUrl = "{{ route('tenant-role-permissions.index') }}";

                if (!roleSwitcher || !container) {
                    return;
                }

                var buildUrl = function(tenantCode, roleCode) {
                    var params = new URLSearchParams();
                    if (tenantCode) {
                        params.set('tenant', tenantCode);
                    }
                    if (roleCode) {
                        params.set('role', roleCode);
                    }
                    var query = params.toString();
                    return query ? (baseUrl + '?' + query) : baseUrl;
                };

                var doAjaxNavigate = function(nextUrl) {
                    if (container.dataset.loading === '1') {
                        return;
                    }
                    container.dataset.loading = '1';
                    container.classList.add('opacity-75');
                    if (tenantSwitcher) {
                        tenantSwitcher.disabled = true;
                    }
                    roleSwitcher.disabled = true;

                    fetch(nextUrl, {
                            headers: {
                                'X-Requested-With': 'XMLHttpRequest'
                            }
                        })
                        .then(function(response) {
                            if (!response.ok) {
                                throw new Error('Failed to load role permission page.');
                            }
                            return response.text();
                        })
                        .then(function(html) {
                            var doc = new DOMParser().parseFromString(html, 'text/html');
                            var freshContainer = doc.getElementById('tenantRolePermissionsAjaxContainer');
                            if (!freshContainer) {
                                throw new Error('Missing refreshed role permission content.');
                            }
                            container.outerHTML = freshContainer.outerHTML;
                            history.pushState({}, '', nextUrl);
                            initRolePermissionAjax();
                        })
                        .catch(function() {
                            window.location.href = nextUrl;
                        })
                        .finally(function() {
                            container.dataset.loading = '0';
                            container.classList.remove('opacity-75');
                            if (tenantSwitcher) {
                                tenantSwitcher.disabled = false;
                            }
                            roleSwitcher.disabled = false;
                        });
                };

                if (tenantSwitcher && !tenantSwitcher.dataset.ajaxBound) {
                    tenantSwitcher.dataset.ajaxBound = '1';
                    tenantSwitcher.addEventListener('change', function() {
                        doAjaxNavigate(buildUrl(this.value, roleSwitcher.value));
                    });
                }

                if (!roleSwitcher.dataset.ajaxBound) {
                    roleSwitcher.dataset.ajaxBound = '1';
                    roleSwitcher.addEventListener('change', function() {
                        var selectedTenant = tenantSwitcher ? tenantSwitcher.value : "{{ $tenant?->code }}";
                        doAjaxNavigate(buildUrl(selectedTenant, this.value));
                    });
                }
            };

            initRolePermissionAjax();
        })();
    </script>
@endsection
