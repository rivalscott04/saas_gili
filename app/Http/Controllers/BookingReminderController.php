<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ChatTemplate;
use App\Services\BookingService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class BookingReminderController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function send(Request $request, Booking $booking): RedirectResponse
    {
        $booking->loadMissing('customer');
        $viewer = $request->user();
        if (! $viewer || ! $viewer->canAccessBooking($booking) || ! $viewer->hasTenantPermission('bookings.send_reminder')) {
            return redirect()->route('root');
        }

        $payload = $request->validate([
            'template_id' => ['required', 'integer'],
        ]);

        $tenantId = $viewer->isSuperAdmin() ? null : $viewer->tenant_id;
        $template = ChatTemplate::query()
            ->whereKey((int) $payload['template_id'])
            ->where('name', 'like', 'WhatsApp%')
            ->where('tenant_id', $tenantId)
            ->first();

        if (! $template) {
            return redirect()
                ->route('index', ['any' => 'apps-bookings'])
                ->with('system_alert', [
                    'reason' => 'REMINDER_TEMPLATE_NOT_FOUND',
                    'icon' => 'warning',
                    'title' => 'Template tidak ditemukan',
                    'message' => 'Pilih template WhatsApp yang tersedia terlebih dahulu.',
                ]);
        }

        $phone = $this->normalizePhone($booking->customer?->phone ?? $booking->customer_phone);
        if ($phone === '') {
            return redirect()
                ->route('index', ['any' => 'apps-bookings'])
                ->with('system_alert', [
                    'reason' => 'REMINDER_PHONE_MISSING',
                    'icon' => 'warning',
                    'title' => 'Nomor WhatsApp belum tersedia',
                    'message' => 'Lengkapi nomor telepon customer sebelum kirim reminder.',
                ]);
        }

        [$booking, $plainToken] = $this->bookingService->generateConfirmationToken($booking);
        $magicLink = config('app.frontend_url').'/booking/'.$booking->id.'/respond?'.http_build_query([
            'token' => $plainToken,
        ]);

        $message = $this->renderTemplate($template->content, $booking, $magicLink);
        $whatsAppUrl = 'https://wa.me/'.$phone.'?'.http_build_query([
            'text' => $message,
        ]);
        $this->logReminderSent($booking, $template, $viewer->id, $phone);

        return redirect()->away($whatsAppUrl);
    }

    private function renderTemplate(string $content, Booking $booking, string $magicLink): string
    {
        $customerName = $booking->customer?->full_name ?? $booking->customer_name ?? 'Customer';
        $tourName = $booking->tour_name ?? 'your booking';
        $tourStartTime = $booking->tour_start_at?->format('d M Y, H:i') ?? '-';
        $greeting = now()->format('H') < 12 ? 'Good morning' : (now()->format('H') < 18 ? 'Good afternoon' : 'Good evening');

        return strtr($content, [
            '{{customerName}}' => $customerName,
            '{{tourName}}' => $tourName,
            '{{tourStartTime}}' => $tourStartTime,
            '{{magicLink}}' => $magicLink,
            '{{greeting}}' => $greeting,
        ]);
    }

    private function normalizePhone(?string $phone): string
    {
        if (! is_string($phone)) {
            return '';
        }

        $normalized = preg_replace('/[^0-9+]/', '', trim($phone)) ?? '';
        if ($normalized === '') {
            return '';
        }

        if (str_starts_with($normalized, '+')) {
            $normalized = substr($normalized, 1);
        }

        if (str_starts_with($normalized, '0')) {
            return '62'.substr($normalized, 1);
        }

        return $normalized;
    }

    private function logReminderSent(Booking $booking, ChatTemplate $template, int $viewerId, string $phone): void
    {
        BookingStatusEvent::query()->create([
            'booking_id' => $booking->id,
            'old_status' => (string) $booking->status,
            'new_status' => (string) $booking->status,
            'changed_by' => 'operator',
            'reason' => 'reminder_sent',
            'source' => 'web',
            'metadata' => [
                'action' => 'whatsapp_reminder',
                'template_id' => $template->id,
                'template_name' => $template->name,
                'sent_to_phone' => $phone,
                'triggered_by_user_id' => $viewerId,
            ],
        ]);
    }
}
