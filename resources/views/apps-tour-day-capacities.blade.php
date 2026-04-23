@extends('layouts.master')

@section('title')
    Kapasitas harian tour
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Operations & Resources
        @endslot
        @slot('title')
            Atur kuota peserta per tanggal
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-xl-4">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Pilih tour</h5>
                </div>
                <div class="card-body">
                    @if ($showTenantSwitcher)
                        <div class="mb-3">
                            <label class="form-label">Tenant</label>
                            <select class="form-select" id="capacityTenantSwitcher">
                                @foreach ($availableTenants as $tenantOption)
                                    <option value="{{ $tenantOption->code }}"
                                        {{ (int) $tenant->id === (int) $tenantOption->id ? 'selected' : '' }}>
                                        {{ $tenantOption->name }} ({{ $tenantOption->code }})
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    @endif
                    <form method="GET" action="{{ route('tour-day-capacities.index') }}" id="tourPickForm">
                        @if ($showTenantSwitcher)
                            <input type="hidden" name="tenant" value="{{ $tenant->code }}">
                        @endif
                        <label class="form-label">Tour utama</label>
                        <select class="form-select mb-3" name="tour_id" required onchange="this.form.submit()">
                            <option value="">— Pilih —</option>
                            @foreach ($tours as $t)
                                <option value="{{ $t->id }}"
                                    {{ $selectedTour && (int) $selectedTour->id === (int) $t->id ? 'selected' : '' }}>
                                    {{ $t->name }}
                                </option>
                            @endforeach
                        </select>
                    </form>
                    <p class="text-muted small mb-0">
                        Jika belum diatur untuk tanggal tertentu, sistem memakai <strong>kuota standar per hari</strong> dari tour utama.
                    </p>
                </div>
            </div>
            @if ($selectedTour)
                <div class="card mt-3">
                    <div class="card-header">
                        <h5 class="card-title mb-0">Tambah / ubah kuota tanggal khusus</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="{{ route('tour-day-capacities.store') }}">
                            @csrf
                            @if ($showTenantSwitcher)
                                <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                            @endif
                            <input type="hidden" name="tour_id" value="{{ $selectedTour->id }}">
                            <div class="mb-3">
                                <label class="form-label">Tanggal layanan</label>
                                <input type="date" class="form-control" name="service_date" required>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Maksimal peserta</label>
                                <input type="number" class="form-control" name="max_pax" min="1" max="100000" required>
                            </div>
                            <button type="submit" class="btn btn-primary w-100">Simpan kuota tanggal ini</button>
                        </form>
                    </div>
                </div>
            @endif
        </div>
        <div class="col-xl-8">
            <div class="card">
                <div class="card-header">
                    <h5 class="card-title mb-0">Daftar kuota tanggal khusus</h5>
                    @if ($selectedTour)
                        <span class="badge bg-primary-subtle text-primary">{{ $selectedTour->name }}</span>
                    @endif
                </div>
                <div class="card-body">
                    @if (! $selectedTour)
                        <div class="alert alert-info mb-0">Pilih tour di kiri untuk mengelola kapasitas harian.</div>
                    @elseif ($capacities->isEmpty())
                        <div class="alert alert-light border mb-0">Belum ada pengaturan tanggal khusus; semua tanggal memakai kuota standar tour.</div>
                    @else
                        <div class="table-responsive">
                            <table class="table align-middle">
                                <thead>
                                    <tr>
                                        <th>Tanggal</th>
                                        <th>Maksimal peserta</th>
                                        <th class="text-end">Aksi</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    @foreach ($capacities as $row)
                                        <tr>
                                            <td>{{ $row->service_date?->format('Y-m-d') }}</td>
                                            <td>{{ $row->max_pax }}</td>
                                            <td class="text-end">
                                                <form method="POST"
                                                    action="{{ route('tour-day-capacities.destroy', $row) }}"
                                                    class="d-inline"
                                                    onsubmit="return confirm('Hapus kuota khusus untuk tanggal ini?');">
                                                    @csrf
                                                    @if ($showTenantSwitcher)
                                                        <input type="hidden" name="tenant_code" value="{{ $tenant->code }}">
                                                    @endif
                                                    <button type="submit" class="btn btn-sm btn-soft-danger">Hapus</button>
                                                </form>
                                            </td>
                                        </tr>
                                    @endforeach
                                </tbody>
                            </table>
                        </div>
                        <div class="d-flex justify-content-between align-items-center flex-wrap gap-2 mt-3">
                            <small class="text-muted">
                                Menampilkan {{ $capacities->firstItem() }} - {{ $capacities->lastItem() }} dari
                                {{ $capacities->total() }} override kapasitas
                            </small>
                            {{ $capacities->links() }}
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </div>
@endsection

@section('script')
    <script>
        (function() {
            var switcher = document.getElementById('capacityTenantSwitcher');
            if (!switcher) {
                return;
            }
            switcher.addEventListener('change', function() {
                var url = "{{ route('tour-day-capacities.index') }}" + '?tenant=' + encodeURIComponent(this.value);
                window.location.href = url;
            });
        })();
    </script>
@endsection
