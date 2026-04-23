@extends('layouts.master')

@section('title')
    Tenant Users
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Tenant
        @endslot
        @slot('title')
            User Management
        @endslot
    @endcomponent

    <div class="row g-3 mb-3">
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Tenant</p>
                    @if ($showTenantSwitcher ?? false)
                        <label class="visually-hidden" for="tenantUserTenantSelect">Pilih tenant</label>
                        <select id="tenantUserTenantSelect" class="form-select form-select-sm"
                            onchange="window.location.href='{{ route('tenant-users.index') }}?tenant=' + encodeURIComponent(this.value)">
                            @foreach ($availableTenants as $tenantOption)
                                <option value="{{ $tenantOption->code }}" {{ (int) $selectedTenantId === (int) $tenantOption->id ? 'selected' : '' }}>
                                    {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                </option>
                            @endforeach
                        </select>
                        <p class="text-muted small mb-0 mt-2">Ganti tenant di dropdown ini.</p>
                    @else
                        <h5 class="mb-0">{{ $tenant?->name ?? 'Tenant' }}</h5>
                    @endif
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Paket Seat</p>
                    <h5 class="mb-0">{{ $totalUsers }}/{{ $maxUsers }} User</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Sisa Seat</p>
                    <h5 class="mb-0">{{ $remainingSeats }}</h5>
                </div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card card-animate">
                <div class="card-body">
                    <p class="text-uppercase fw-medium text-muted mb-2">Role Custom</p>
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
                            <h5 class="card-title mb-1">Daftar User Tenant</h5>
                            <p class="text-muted mb-0">Kelola user dan role tenant sesuai kuota paket.</p>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-soft-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenantRoleModal">
                                <i class="ri-shield-user-line align-bottom me-1"></i> Tambah Role
                            </button>
                        </div>
                        <div class="flex-shrink-0">
                            <button class="btn btn-primary" type="button" data-bs-toggle="modal" data-bs-target="#createTenantUserModal">
                                <i class="ri-user-add-line align-bottom me-1"></i> Tambah User
                            </button>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>Nama</th>
                                    <th>Email</th>
                                    <th>Role</th>
                                    <th>Status</th>
                                    <th>Dibuat</th>
                                    <th class="text-end">Aksi</th>
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
                                                <span class="badge bg-success-subtle text-success">Active</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Suspended</span>
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
                                                    data-confirm-title="{{ $isActive ? 'Suspend user ini?' : 'Aktifkan kembali user ini?' }}"
                                                    data-confirm-text="{{ $isActive ? 'User tidak bisa login sampai diaktifkan kembali.' : 'User akan bisa login kembali.' }}"
                                                    data-confirm-button="{{ $isActive ? 'Ya, Suspend' : 'Ya, Aktifkan' }}">
                                                    {{ $isActive ? 'Suspend' : 'Activate' }}
                                                </button>
                                            </form>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="6" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-user-search-line fs-2 d-block mb-2"></i>
                                                Belum ada user di tenant ini. Tambahkan user pertama untuk mulai kolaborasi.
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
                                Menampilkan {{ $users->firstItem() }} - {{ $users->lastItem() }} dari
                                {{ $users->total() }} user
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
                        <h5 class="modal-title" id="createTenantUserModalLabel">Tambah User Tenant</h5>
                        <p class="text-muted mb-0">Buat akun baru dan atur role aksesnya.</p>
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
                                <label class="form-label">Nama Lengkap</label>
                                <input type="text" name="name" class="form-control @error('name') is-invalid @enderror"
                                    value="{{ old('name') }}" placeholder="Contoh: Budi Santoso" autocomplete="name" required>
                                @error('name')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Email</label>
                                <input type="email" name="email" class="form-control @error('email') is-invalid @enderror"
                                    value="{{ old('email') }}" placeholder="nama@perusahaan.com" autocomplete="email" required>
                                @error('email')
                                    <div class="invalid-feedback">{{ $message }}</div>
                                @enderror
                            </div>
                            <div class="col-md-6">
                                <label class="form-label">Role</label>
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
                                <label class="form-label">Password</label>
                                <div class="input-group">
                                    <input id="passwordInput" type="password" name="password"
                                        class="form-control @error('password') is-invalid @enderror"
                                        value="{{ old('password') }}" minlength="8"
                                        placeholder="Minimal 8 karakter" autocomplete="new-password" required>
                                    <button class="btn btn-soft-primary" type="button" id="togglePasswordBtn"
                                        aria-label="Tampilkan atau sembunyikan password">Lihat</button>
                                    <button class="btn btn-soft-info" type="button" id="generatePasswordBtn">Generate</button>
                                </div>
                                @error('password')
                                    <div class="invalid-feedback d-block">{{ $message }}</div>
                                @enderror
                            </div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-user-add-line align-bottom me-1"></i> Simpan User
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
                    <h5 class="modal-title" id="createTenantRoleModalLabel">Tambah Role Custom</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <form method="POST" action="{{ route('tenant-users.store-role') }}">
                    @csrf
                    @if ($showTenantSwitcher ?? false)
                        <input type="hidden" name="tenant_code" value="{{ $tenant?->code }}">
                    @endif
                    <div class="modal-body">
                        <div class="alert alert-info">
                            Batas role custom mengikuti sisa seat paket: <strong>{{ $customRolesCount }}/{{ $maxCustomRoles }}</strong>.
                        </div>
                        <div class="mb-0">
                            <label class="form-label">Nama Role</label>
                            <input type="text" name="role_name" class="form-control @error('role_name') is-invalid @enderror" maxlength="120"
                                placeholder="Contoh: Sales Coordinator" required>
                            @error('role_name')
                                <div class="invalid-feedback">{{ $message }}</div>
                            @enderror
                            <div class="form-text">Setelah role dibuat, atur permission di menu Role Permissions.</div>
                        </div>
                    </div>
                    <div class="modal-footer">
                        <button type="button" class="btn btn-light" data-bs-dismiss="modal">Batal</button>
                        <button type="submit" class="btn btn-primary">
                            <i class="ri-add-line align-bottom me-1"></i> Simpan Role
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('DOMContentLoaded', function() {
            const passwordInput = document.getElementById('passwordInput');
            const togglePasswordBtn = document.getElementById('togglePasswordBtn');
            const generatePasswordBtn = document.getElementById('generatePasswordBtn');
            const userModalElement = document.getElementById('createTenantUserModal');
            const roleModalElement = document.getElementById('createTenantRoleModal');

            if (togglePasswordBtn && passwordInput) {
                togglePasswordBtn.addEventListener('click', function() {
                    const isHidden = passwordInput.type === 'password';
                    passwordInput.type = isHidden ? 'text' : 'password';
                    togglePasswordBtn.textContent = isHidden ? 'Sembunyikan' : 'Lihat';
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
                        togglePasswordBtn.textContent = 'Sembunyikan';
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
                        title: button.getAttribute('data-confirm-title') || 'Anda yakin?',
                        text: button.getAttribute('data-confirm-text') || '',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: button.getAttribute('data-confirm-button') || 'Ya',
                        cancelButtonText: 'Batal',
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
        });
    </script>
@endsection
