<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\BookingResource;
use App\Models\Booking;
use App\Services\DashboardService;
use Illuminate\Http\Request;

class DashboardController extends Controller
{
    public function __construct(private readonly DashboardService $dashboardService)
    {
    }

    public function summary(Request $request)
    {
        $this->authorize('viewAny', Booking::class);
        $viewer = $request->user();
        $tenantId = $this->resolveTenantScope($request);
        $summary = $this->dashboardService->summary($viewer, $tenantId);
        if (! $viewer->isAdmin()) {
            unset(
                $summary['gross_sales'],
                $summary['net_revenue'],
                $summary['revenue_idr']
            );
        }

        return response()->json([
            'data' => $summary,
        ]);
    }

    public function urgentBookings(Request $request)
    {
        $this->authorize('viewAny', Booking::class);
        $viewer = $request->user();
        $tenantId = $this->resolveTenantScope($request);

        return BookingResource::collection(
            $this->dashboardService->urgentBookings($viewer, (int) $request->query('limit', 3), $tenantId)
        );
    }

    public function recentBookings(Request $request)
    {
        $this->authorize('viewAny', Booking::class);
        $viewer = $request->user();
        $tenantId = $this->resolveTenantScope($request);

        return BookingResource::collection(
            $this->dashboardService->recentBookings($viewer, (int) $request->query('limit', 6), $tenantId)
        );
    }

    private function resolveTenantScope(Request $request): ?int
    {
        $viewer = $request->user();
        if (! $viewer || ! $viewer->isSuperAdmin()) {
            return null;
        }

        $tenantId = $request->query('tenant_id');
        if (! is_numeric($tenantId)) {
            return null;
        }

        return (int) $tenantId;
    }
}
