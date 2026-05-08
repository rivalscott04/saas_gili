@extends('layouts.master')

@section('title')
    Manage Tenants
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Superadmin
        @endslot
        @slot('title')
            Manage Tenants
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card border-0 shadow-sm">
                <div class="card-header bg-white border-0">
                    <div class="d-flex align-items-center gap-2 flex-wrap">
                        <div class="flex-grow-1">
                            <h5 class="card-title mb-1">Daftar Tenant</h5>
                            <p class="text-muted mb-0">Aktif/nonaktifkan tenant atau hapus tenant.</p>
                        </div>
                    </div>
                    <form method="GET" action="{{ route('superadmin.tenants.index') }}" class="row g-2 mt-3">
                        <div class="col-md-5">
                            <input type="text" name="q" class="form-control" value="{{ $filters['q'] ?? '' }}"
                                placeholder="Cari nama / kode tenant">
                        </div>
                        <div class="col-md-3">
                            <select name="status" class="form-select">
                                <option value="">Semua status</option>
                                <option value="active" {{ ($filters['status'] ?? '') === 'active' ? 'selected' : '' }}>Aktif</option>
                                <option value="inactive" {{ ($filters['status'] ?? '') === 'inactive' ? 'selected' : '' }}>Nonaktif</option>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-primary w-100">Filter</button>
                        </div>
                        <div class="col-md-2">
                            <a href="{{ route('superadmin.tenants.index') }}" class="btn btn-light w-100">Reset</a>
                        </div>
                    </form>
                </div>
                <div class="card-body">
                    <div class="table-responsive table-card">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>Nama</th>
                                    <th>Kode</th>
                                    <th>Status</th>
                                    <th>Created</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($tenants as $tenant)
                                    <tr>
                                        <td class="fw-semibold">{{ $tenant->name }}</td>
                                        <td><span class="badge bg-light text-muted">{{ $tenant->code }}</span></td>
                                        <td>
                                            @if ($tenant->is_active)
                                                <span class="badge bg-success-subtle text-success">Aktif</span>
                                            @else
                                                <span class="badge bg-danger-subtle text-danger">Nonaktif</span>
                                            @endif
                                        </td>
                                        <td>{{ optional($tenant->created_at)->format('d M Y H:i') }}</td>
                                        <td class="text-end">
                                            <div class="hstack gap-1 justify-content-end">
                                                <form method="POST" action="{{ route('superadmin.tenants.update-status', $tenant) }}" class="d-inline">
                                                    @csrf
                                                    <input type="hidden" name="is_active" value="{{ $tenant->is_active ? '0' : '1' }}">
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm {{ $tenant->is_active ? 'btn-soft-warning' : 'btn-soft-success' }} js-tenant-status-confirm"
                                                        data-confirm-title="{{ $tenant->is_active ? 'Nonaktifkan tenant?' : 'Aktifkan tenant?' }}"
                                                        data-confirm-text="{{ $tenant->is_active ? 'Tenant akan tidak bisa digunakan login/operasional.' : 'Tenant akan aktif kembali dan bisa digunakan.' }}"
                                                        data-confirm-button="{{ $tenant->is_active ? 'Ya, nonaktifkan' : 'Ya, aktifkan' }}"
                                                    >
                                                        {{ $tenant->is_active ? 'Disable' : 'Enable' }}
                                                    </button>
                                                </form>
                                                <form method="POST" action="{{ route('superadmin.tenants.destroy', $tenant) }}" class="d-inline">
                                                    @csrf
                                                    <button
                                                        type="submit"
                                                        class="btn btn-sm btn-soft-danger js-tenant-delete-confirm"
                                                        data-tenant-name="{{ $tenant->name }}"
                                                        {{ $tenant->code === 'default' ? 'disabled' : '' }}
                                                    >
                                                        Delete
                                                    </button>
                                                </form>
                                            </div>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="5" class="text-center py-5">
                                            <div class="text-muted">
                                                <i class="ri-building-line fs-2 d-block mb-2"></i>
                                                Tenant belum ada.
                                            </div>
                                        </td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                    @if ($tenants->count() > 0)
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                Menampilkan {{ $tenants->firstItem() }}-{{ $tenants->lastItem() }} dari {{ $tenants->total() }} tenant
                            </small>
                            {{ $tenants->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const statusButtons = document.querySelectorAll('.js-tenant-status-confirm');
            statusButtons.forEach(function (button) {
                if (button.dataset.confirmBound) {
                    return;
                }
                button.dataset.confirmBound = '1';
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const form = button.closest('form');
                    if (!form || typeof Swal === 'undefined') {
                        if (form) {
                            form.submit();
                        }
                        return;
                    }

                    Swal.fire({
                        title: button.getAttribute('data-confirm-title') || 'Konfirmasi',
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
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });

            const deleteButtons = document.querySelectorAll('.js-tenant-delete-confirm');
            deleteButtons.forEach(function (button) {
                if (button.dataset.confirmBound) {
                    return;
                }
                button.dataset.confirmBound = '1';
                button.addEventListener('click', function (event) {
                    event.preventDefault();
                    const form = button.closest('form');
                    if (!form || typeof Swal === 'undefined') {
                        if (form) {
                            form.submit();
                        }
                        return;
                    }

                    const name = button.getAttribute('data-tenant-name') || 'tenant ini';
                    Swal.fire({
                        title: 'Hapus tenant?',
                        text: 'Tenant "' + name + '" akan dihapus permanen. Lanjutkan?',
                        icon: 'warning',
                        showCancelButton: true,
                        confirmButtonText: 'Ya, hapus',
                        cancelButtonText: 'Batal',
                        customClass: {
                            confirmButton: 'btn btn-danger w-xs me-2 mt-2',
                            cancelButton: 'btn btn-light w-xs mt-2',
                        },
                        buttonsStyling: false,
                        showCloseButton: true
                    }).then(function (result) {
                        if (result.isConfirmed) {
                            form.submit();
                        }
                    });
                });
            });
        });
    </script>
@endsection

