<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Models\Booking;
use App\Services\AnalyticsService;
use Illuminate\Http\Request;

class AnalyticsController extends Controller
{
    public function __construct(private readonly AnalyticsService $analyticsService)
    {
    }

    public function overview(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        return response()->json([
            'data' => $this->analyticsService->overview($request->user()),
        ]);
    }

    public function trends(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $period = $request->query('period', 'weekly');
        if (! in_array($period, ['weekly', 'monthly'], true)) {
            $period = 'weekly';
        }

        return response()->json([
            'data' => $this->analyticsService->trends($request->user(), $period),
        ]);
    }

    public function exportCsv(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        return $this->analyticsService->exportBookingsCsv($request->user());
    }
}
