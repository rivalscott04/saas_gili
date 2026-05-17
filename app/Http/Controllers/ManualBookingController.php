<?php

namespace App\Http\Controllers;

use App\Http\Requests\StoreManualBookingRequest;
use App\Models\Booking;
use App\Models\Tenant;
use App\Models\Tour;
use App\Models\User;
use App\Services\BookingService;
use App\Support\TenantPicker;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Collection;
use Illuminate\View\View;

class ManualBookingController extends Controller
{
    public function __construct(private readonly BookingService $bookingService)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function create(Request $request): View
    {
        $this->authorize('create', Booking::class);

        $viewer = $request->user();
        $tenantOptions = $viewer !== null && $viewer->isSuperAdmin()
            ? TenantPicker::optionsForSuperAdmin()
            : collect();

        return view('apps-bookings-manual-create', [
            'tenantOptions' => $tenantOptions,
            'canViewRevenue' => $viewer !== null && $viewer->isAdmin(),
            'guideUsers' => $this->loadGuideUsersForPicker($viewer, $tenantOptions),
            'tourOptions' => $this->loadToursForPicker($viewer, $tenantOptions),
        ]);
    }

    /**
     * @param  Collection<int, Tenant>  $tenantOptions
     * @return Collection<int, User>
     */
    private function loadGuideUsersForPicker(?User $viewer, Collection $tenantOptions): Collection
    {
        if ($viewer === null) {
            return collect();
        }

        $query = User::query()
            ->where('role', 'guide')
            ->where(function ($q): void {
                $q->whereNull('status')
                    ->orWhereRaw('LOWER(status) != ?', ['suspended']);
            });

        $pickerLimit = max(50, (int) config('bookings.manual_picker_limit', 500));

        if ($viewer->isSuperAdmin() && $tenantOptions->isNotEmpty()) {
            return $query->clone()
                ->whereIn('tenant_id', $tenantOptions->pluck('id')->all())
                ->with(['tenant:id,name'])
                ->orderBy('tenant_id')
                ->orderBy('name')
                ->limit($pickerLimit)
                ->get(['id', 'name', 'tenant_id']);
        }

        if ($viewer->tenant_id === null) {
            return collect();
        }

        return $query->clone()
            ->where('tenant_id', $viewer->tenant_id)
            ->orderBy('name')
            ->get(['id', 'name', 'tenant_id']);
    }

    /**
     * @param  Collection<int, Tenant>  $tenantOptions
     * @return Collection<int, Tour>
     */
    private function loadToursForPicker(?User $viewer, Collection $tenantOptions): Collection
    {
        if ($viewer === null) {
            return collect();
        }

        $query = Tour::query()
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->orderBy('name');

        $pickerLimit = max(50, (int) config('bookings.manual_picker_limit', 500));

        if ($viewer->isSuperAdmin() && $tenantOptions->isNotEmpty()) {
            return $query->clone()
                ->whereIn('tenant_id', $tenantOptions->pluck('id')->all())
                ->with(['tenant:id,name'])
                ->orderBy('tenant_id')
                ->limit($pickerLimit)
                ->get(['id', 'tenant_id', 'name', 'code']);
        }

        if ($viewer->tenant_id === null) {
            return collect();
        }

        return $query->clone()
            ->where('tenant_id', $viewer->tenant_id)
            ->get(['id', 'tenant_id', 'name', 'code']);
    }

    public function store(StoreManualBookingRequest $request): RedirectResponse
    {
        $this->authorize('create', Booking::class);

        $this->bookingService->createManualBooking($request->user(), $request->validated());

        return redirect()->to(url('/apps-bookings'));
    }
}
