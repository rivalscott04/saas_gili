@extends('layouts.master-without-nav')

@section('title')
Booking Response
@endsection

@section('body')
<body class="magic-link-page">
@endsection

@section('content')
<style>
    body.magic-link-page > .container-fluid.pt-3 {
        display: none !important;
    }
</style>
<div class="auth-page-wrapper auth-bg-cover py-0 d-flex justify-content-center align-items-center min-vh-100">
    <div class="bg-overlay"></div>
    <div class="auth-page-content overflow-hidden pt-0">
        <div class="container">
            <div class="row justify-content-center">
                <div class="col-lg-8">
                    <div class="card overflow-hidden">
                        <div class="row g-0">
                            <div class="col-lg-5">
                                <div class="p-lg-5 p-4 auth-one-bg h-100">
                                    <div class="bg-overlay"></div>
                                    <div class="position-relative h-100 d-flex flex-column">
                                        <div class="mb-4">
                                            <span class="d-inline-block">
                                                <img src="{{ URL::asset('build/images/logo-light.png') }}" alt="Logo" height="18">
                                            </span>
                                        </div>
                                        <div class="mt-auto text-white">
                                            <h5 class="text-white mb-2">Booking Confirmation</h5>
                                            <p class="mb-0 text-white-75">Please review your booking details and choose one response.</p>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-lg-7">
                                <div class="p-lg-5 p-4">
                                    @php
                                        $guestName = trim((string) ($booking->customer?->full_name ?? $booking->customer_name ?? ''));
                                        if ($guestName === '') {
                                            $guestName = 'Guest';
                                        }
                                    @endphp
                                    <h5 class="text-primary mb-1">{{ $booking->tour_name ?? 'Booking' }}</h5>
                                    <p class="text-muted mb-1">Hi {{ $guestName }}, please review your booking details below.</p>
                                    <p class="text-muted mb-4">
                                        If everything looks good and you do not have any obstacles, you can confirm attendance.
                                        Otherwise, please choose reschedule or cancel. Please pick one option below.
                                    </p>

                                    @php
                                        $flash = session('magic_link_alert');
                                        $icon = data_get($flash, 'icon', 'info');
                                        $alertClass = match ($icon) {
                                            'success' => 'alert-success',
                                            'warning' => 'alert-warning',
                                            'danger' => 'alert-danger',
                                            default => 'alert-info',
                                        };
                                    @endphp
                                    @if ($flash)
                                        <div class="alert {{ $alertClass }} mb-3">
                                            {{ data_get($flash, 'message') }}
                                        </div>
                                    @endif

                                    @if ($message && ! $flash)
                                        <div class="alert alert-info mb-3">{{ $message }}</div>
                                    @endif

                                    <div class="table-responsive mb-4">
                                        <table class="table table-sm align-middle mb-0">
                                            <tbody>
                                                <tr>
                                                    <th class="text-muted">PAX</th>
                                                    <td>{{ $booking->participants ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Tour Date</th>
                                                    <td>{{ $booking->tour_start_at?->format('d M Y, H:i') ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Status</th>
                                                    <td><span class="badge bg-primary-subtle text-primary text-uppercase">{{ str_replace('_', ' ', (string) $booking->status) }}</span></td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Guide</th>
                                                    <td>{{ $booking->guide_name ?? '-' }}</td>
                                                </tr>
                                                <tr>
                                                    <th class="text-muted">Location</th>
                                                    <td>{{ $booking->location ?? '-' }}</td>
                                                </tr>
                                            </tbody>
                                        </table>
                                    </div>

                                    @if ($state === 'form')
                                        <form method="POST" action="{{ route('bookings.magic-link.submit', $booking->id) }}">
                                            @csrf
                                            <input type="hidden" name="token" value="{{ $token }}">
                                            <div class="d-grid gap-2">
                                                <button type="submit" name="action" value="confirm" class="btn btn-success">
                                                    <i class="ri-checkbox-circle-line align-bottom me-1"></i> Confirm Attendance
                                                </button>
                                                <button type="submit" name="action" value="reschedule" class="btn btn-warning">
                                                    <i class="ri-calendar-event-line align-bottom me-1"></i> Request Reschedule
                                                </button>
                                                <button type="submit" name="action" value="cancel" class="btn btn-danger">
                                                    <i class="ri-close-circle-line align-bottom me-1"></i> Cancel Booking
                                                </button>
                                            </div>
                                        </form>
                                    @else
                                        <div class="alert alert-light border mb-0">
                                            This response page is no longer actionable.
                                        </div>
                                    @endif
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>
@endsection
