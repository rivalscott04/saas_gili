@extends('layouts.master')

@section('title')
    Role Permissions
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Tenant
        @endslot
        @slot('title')
            Role Permissions
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Permission Dinamis Per Role</h5>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary">{{ $tenant?->name ?? 'Tenant' }}</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <form method="POST" action="{{ route('tenant-role-permissions.update') }}">
                        @csrf
                        <div class="row mb-4">
                            @if (auth()->user()?->isSuperAdmin())
                                <div class="col-lg-4">
                                    <label class="form-label">Tenant</label>
                                    <select class="form-select"
                                        onchange="window.location.href='{{ route('tenant-role-permissions.index') }}?tenant=' + encodeURIComponent(this.value) + '&role={{ $selectedRole }}'">
                                        @foreach ($availableTenants as $tenantOption)
                                            <option value="{{ $tenantOption->code }}" {{ ($tenant?->code ?? '') === (string) $tenantOption->code ? 'selected' : '' }}>
                                                {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                            </option>
                                        @endforeach
                                    </select>
                                </div>
                            @endif
                            <div class="col-lg-4">
                                <label class="form-label">Role</label>
                                <select name="role" class="form-select"
                                    onchange="window.location.href='{{ route('tenant-role-permissions.index') }}?tenant={{ $tenant?->code }}&role=' + encodeURIComponent(this.value)">
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
                                        <th>Akses</th>
                                        <th>Keterangan</th>
                                        <th class="text-end">Allow</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($permissions as $item)
                                        <tr>
                                            <td class="fw-semibold">{{ $item['label'] }}</td>
                                            <td>Izin untuk {{ strtolower($item['label']) }}.</td>
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
                                <i class="ri-save-line align-bottom me-1"></i> Simpan Permission
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
@endsection
