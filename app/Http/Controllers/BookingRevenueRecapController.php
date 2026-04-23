<?php

namespace App\Http\Controllers;

use App\Services\BookingRevenueRecapService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\StreamedResponse;
use Illuminate\View\View;

class BookingRevenueRecapController extends Controller
{
    public function __construct(private readonly BookingRevenueRecapService $service)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $filters = [
            'specific_date' => (string) $request->query('specific_date', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'channel' => (string) $request->query('channel', ''),
        ];

        $recap = $this->service->recap($viewer, $filters);

        return view('apps-bookings-recap', [
            'filters' => $filters,
            'summary' => $recap['summary'],
            'perChannel' => $recap['per_channel'],
            'trendDaily' => $recap['trend_daily'],
            'channels' => $recap['channels'],
        ]);
    }

    public function export(Request $request): StreamedResponse|RedirectResponse
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isAdmin()) {
            return redirect()->route('root');
        }

        $filters = [
            'specific_date' => (string) $request->query('specific_date', ''),
            'date_from' => (string) $request->query('date_from', ''),
            'date_to' => (string) $request->query('date_to', ''),
            'channel' => (string) $request->query('channel', ''),
        ];
        $format = strtolower((string) $request->query('format', 'csv'));
        if (! in_array($format, ['csv', 'excel'], true)) {
            $format = 'csv';
        }
        $delimiter = strtolower((string) $request->query('delimiter', 'semicolon'));
        if (! in_array($delimiter, ['semicolon', 'colon'], true)) {
            $delimiter = 'semicolon';
        }

        return $this->service->export($viewer, $filters, $format, $delimiter);
    }
}
