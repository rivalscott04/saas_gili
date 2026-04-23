@extends('layouts.master')

@section('title')
    Invoice Booking
@endsection

@section('content')
    @component('components.breadcrumb')
        @slot('li_1')
            Billing
        @endslot
        @slot('title')
            Invoice Booking
        @endslot
    @endcomponent

    <div class="row">
        <div class="col-12">
            <div class="card">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Branding Invoice Tenant</h5>
                        <div class="flex-shrink-0">
                    @if (auth()->user()?->isAdmin())
                        <form action="{{ route('tenant-invoices.branding') }}" method="POST" enctype="multipart/form-data"
                            class="d-flex gap-2 align-items-center">
                            @csrf
                            <input type="file" class="form-control form-control-sm" name="invoice_logo"
                                accept=".png,.jpg,.jpeg,.webp" required>
                            <button class="btn btn-sm btn-primary" type="submit">Upload Logo</button>
                        </form>
                    @endif
                        </div>
                    </div>
                </div>
                <div class="card-body bg-light-subtle border border-dashed border-start-0 border-end-0">
                    <div class="d-flex align-items-center gap-3 flex-wrap">
                        <div>
                            <p class="text-uppercase fw-medium text-muted mb-1">Tenant</p>
                            <h6 class="mb-0">{{ $tenant?->name ?? 'Default Tenant' }}</h6>
                        </div>
                        <div class="ms-auto">
                            @if ($tenant?->invoice_logo_path)
                                <img src="{{ asset('storage/' . $tenant->invoice_logo_path) }}" alt="Tenant Logo"
                                    style="max-height: 48px;">
                            @else
                                <span class="badge bg-warning-subtle text-warning">Belum ada logo invoice</span>
                            @endif
                        </div>
                    </div>
                    @error('invoice_logo')
                        <div class="alert alert-danger mt-3 mb-0">{{ $message }}</div>
                    @enderror
                </div>
            </div>
        </div>
    </div>

    <div class="row">
        <div class="col-12">
            <div class="card" id="invoiceList">
                <div class="card-header border-0">
                    <div class="d-flex align-items-center">
                        <h5 class="card-title mb-0 flex-grow-1">Daftar Invoice (Dari Booking)</h5>
                        <div class="flex-shrink-0">
                            <span class="badge bg-primary-subtle text-primary">{{ $bookings->count() }} Invoice</span>
                        </div>
                    </div>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table align-middle table-nowrap mb-0">
                            <thead class="table-light text-muted text-uppercase">
                                <tr>
                                    <th>No Invoice</th>
                                    <th>Guest</th>
                                    <th>Tour</th>
                                    <th>Tanggal Tour</th>
                                    <th>Status Booking</th>
                                    <th class="text-end">Jumlah tagihan</th>
                                    <th class="text-end">Aksi</th>
                                </tr>
                            </thead>
                            <tbody>
                                @forelse ($bookings as $booking)
                                    @php
                                        $invoiceNumber = 'INV-' . optional($booking->tour_start_at)->format('Ymd') . '-' . str_pad((string) $booking->id, 5, '0', STR_PAD_LEFT);
                                    @endphp
                                    <tr>
                                        <td class="fw-semibold">{{ $invoiceNumber }}</td>
                                        <td>{{ $booking->customer?->full_name ?? $booking->customer_name ?? '-' }}</td>
                                        <td>{{ $booking->tour_name ?? '-' }}</td>
                                        <td>{{ optional($booking->tour_start_at)->format('d M Y H:i') ?? '-' }}</td>
                                        <td><span class="badge bg-info-subtle text-info">{{ ucfirst((string) $booking->status) }}</span></td>
                                        <td class="text-end">
                                            @php
                                                $cur = strtoupper((string) ($booking->currency ?? 'IDR'));
                                                $gross = (float) ($booking->gross_amount ?? 0);
                                                $net = (float) ($booking->net_amount ?? 0);
                                                $payable = $gross > 0 ? $gross : $net;
                                            @endphp
                                            @if($payable > 0)
                                                <span class="fw-semibold">{{ $cur }} {{ number_format($payable, 2) }}</span>
                                            @else
                                                <span class="text-muted">—</span>
                                            @endif
                                        </td>
                                        <td class="text-end">
                                            <a href="{{ route('tenant-invoices.show', $booking) }}" class="btn btn-sm btn-soft-primary">
                                                Lihat Invoice
                                            </a>
                                        </td>
                                    </tr>
                                @empty
                                    <tr>
                                        <td colspan="7" class="text-center text-muted py-4">Belum ada booking untuk dibuat invoice.</td>
                                    </tr>
                                @endforelse
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
@endsection
