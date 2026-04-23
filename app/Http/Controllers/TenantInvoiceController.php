<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Support\ValidationMessages\TenantInvoiceValidationMessages;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;
use Illuminate\View\View;

class TenantInvoiceController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->hasTenantPermission('invoices.view')) {
            abort(403);
        }

        $bookings = Booking::query()
            ->visibleToUser($viewer)
            ->with('customer')
            ->orderByDesc('tour_start_at')
            ->orderByDesc('id')
            ->limit(300)
            ->get();

        return view('apps-invoices', [
            'bookings' => $bookings,
            'tenant' => $viewer->tenant,
        ]);
    }

    public function show(Request $request, Booking $booking): View
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->hasTenantPermission('invoices.view') || ! $viewer->canAccessBooking($booking)) {
            abort(403);
        }

        $booking->loadMissing('customer');

        return view('apps-invoices-show', [
            'booking' => $booking,
            'tenant' => $viewer->tenant,
        ]);
    }

    public function updateBranding(Request $request): RedirectResponse
    {
        $viewer = $request->user();
        $tenant = $viewer->tenant;

        if (! $viewer->isAdmin() || ! $tenant) {
            abort(403);
        }

        $payload = $request->validate([
            'invoice_logo' => ['required', 'image', 'mimes:jpg,jpeg,png,webp', 'max:2048'],
        ], TenantInvoiceValidationMessages::branding());

        if ($tenant->invoice_logo_path) {
            Storage::disk('public')->delete($tenant->invoice_logo_path);
        }

        $tenant->invoice_logo_path = $payload['invoice_logo']->store('tenant-invoice-logos', 'public');
        $tenant->save();

        return redirect()
            ->back()
            ->with('system_alert', [
                'icon' => 'success',
                'title' => 'Invoice logo updated',
                'message' => 'Logo invoice tenant berhasil diperbarui.',
            ]);
    }
}
