<?php

namespace App\Http\Controllers;

use App\Models\Booking;
use App\Models\BookingResourceAllocation;
use App\Models\ChatTemplate;
use App\Models\LandingPricingPlan;
use App\Models\Tenant;
use App\Models\TenantOnboardingState;
use App\Models\TenantResource;
use App\Models\User;
use App\Services\BookingListHistoryService;
use App\Services\DashboardService;
use App\Services\OnboardingService;
use App\Services\TourAllocationRuleService;
use App\Services\UserAccessLogService;
use App\Support\BookingListFilterCounts;
use App\Support\LandingPricingCache;
use App\Support\TenantPicker;
use Illuminate\Http\Request;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Session;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(
        private readonly DashboardService $dashboardService,
        private readonly UserAccessLogService $userAccessLogService,
        private readonly BookingListHistoryService $bookingListHistoryService,
        private readonly TourAllocationRuleService $allocationRuleService,
        private readonly OnboardingService $onboardingService,
    ) {
        $this->middleware('auth')->except(['root']);
        $this->middleware('ensure.user.access')->except(['root']);
    }

    /**
     * Show the application dashboard.
     *
     * @return \Illuminate\Contracts\Support\Renderable
     */
    public function index(Request $request)
    {
        if ($request->path() === 'dashboard-analytics') {
            $viewer = $request->user();
            $selectedTenantId = null;
            $tenantOptions = collect();
            if ($viewer && $viewer->isSuperAdmin()) {
                $tenantOptions = TenantPicker::optionsForSuperAdmin();
                $requestedTenantCode = trim((string) $request->query('tenant', ''));
                if ($requestedTenantCode !== '') {
                    $selectedTenantId = $tenantOptions->first(
                        fn (Tenant $candidate): bool => strtolower((string) $candidate->code) === strtolower($requestedTenantCode)
                    )?->id;
                }
            }

            $isSuperAdminViewer = $viewer?->isSuperAdmin() ?? false;
            $channelGeography = $isSuperAdminViewer
                ? $this->dashboardService->channelGeographyAnalytics($viewer, $selectedTenantId)
                : null;
            $superadminPlatform = $isSuperAdminViewer
                ? $this->dashboardService->platformSummary($selectedTenantId)
                : null;
            $liveUsersGeography = $isSuperAdminViewer
                ? $this->userAccessLogService->liveUsersByCountry($selectedTenantId)
                : null;

            return view('dashboard-analytics', [
                'summary' => $this->dashboardService->summary($viewer, $selectedTenantId),
                'urgentBookings' => $this->dashboardService->urgentBookings($viewer, 5, $selectedTenantId),
                'recentBookings' => $this->dashboardService->recentBookings($viewer, 8, $selectedTenantId),
                'channelGeography' => $channelGeography,
                'liveUsersGeography' => $liveUsersGeography,
                'superadminPlatform' => $superadminPlatform,
                'tenantOptions' => $tenantOptions,
                'selectedTenantId' => $selectedTenantId,
                'selectedTenantCode' => $selectedTenantId
                    ? (string) optional($tenantOptions->firstWhere('id', $selectedTenantId))->code
                    : '',
                'isSuperAdminViewer' => $isSuperAdminViewer,
                'canViewRevenue' => $viewer?->isAdmin() ?? false,
            ]);
        }

        if ($request->path() === 'apps-bookings-calendar') {
            $viewer = $request->user();
            if (! $viewer || ! $viewer->hasTenantPermission('bookings.view')) {
                return abort(403);
            }
            $calendarStart = Carbon::now()->subMonths(2)->startOfDay();
            $calendarEnd = Carbon::now()->addMonths(6)->endOfDay();
            $bookings = Booking::query()
                ->visibleToUser($viewer)
                ->with('customer')
                ->whereNotNull('tour_start_at')
                ->whereBetween('tour_start_at', [$calendarStart, $calendarEnd])
                ->orderBy('tour_start_at')
                ->limit(500)
                ->get();

            $bookingCalendarEvents = $bookings->map(function (Booking $booking): array {
                $bookingSource = strtolower((string) ($booking->booking_source ?? 'manual'));
                $bookingSource = in_array($bookingSource, ['manual', 'ota'], true) ? $bookingSource : 'manual';
                $channelLabel = $booking->channel ? strtoupper((string) $booking->channel) : null;

                return [
                    'id' => $booking->id,
                    'title' => trim(($booking->customer?->full_name ?? $booking->customer_name ?? 'Guest').' - '.($booking->tour_name ?? 'Tour')),
                    'start' => optional($booking->tour_start_at)?->toIso8601String(),
                    'status' => $booking->status,
                    'extendedProps' => [
                        'guestName' => $booking->customer?->full_name ?? $booking->customer_name,
                        'packageName' => $booking->tour_name,
                        'pax' => $booking->participants,
                        'guide' => $booking->guide_name,
                        'pickupPoint' => $booking->location,
                        'status' => ucfirst((string) $booking->status),
                        'bookingSource' => $bookingSource,
                        'sourceLabel' => strtoupper($bookingSource),
                        'channelLabel' => $channelLabel,
                        'notes' => $booking->notes,
                        'assignedToName' => $booking->assigned_to_name,
                        'needsAttention' => (bool) $booking->needs_attention,
                    ],
                ];
            })->values();

            return view('apps-bookings-calendar', [
                'bookingCalendarEvents' => $bookingCalendarEvents,
            ]);
        }

        if ($request->path() === 'apps-bookings') {
            $viewer = $request->user();
            if (! $viewer || ! $viewer->hasTenantPermission('bookings.view')) {
                return abort(403);
            }
            $todayStart = Carbon::now()->startOfDay();
            $h3End = Carbon::now()->addDays(3)->endOfDay();
            $bookings = Booking::query()
                ->visibleToUser($viewer)
                ->with([
                    'customer',
                    'latestReschedule',
                    'tour:id,name,allocation_requirement',
                    'tour.resourceRequirements:id,tour_id,resource_type,is_required,min_units',
                ])
                ->withCount('reschedules')
                ->withMax('reschedules', 'created_at')
                ->orderByRaw(
                    'CASE
                        WHEN tour_start_at IS NULL THEN 3
                        WHEN tour_start_at < ? THEN 2
                        WHEN tour_start_at BETWEEN ? AND ? THEN 0
                        ELSE 1
                    END ASC',
                    [$todayStart, $todayStart, $h3End]
                )
                ->orderBy('tour_start_at')
                ->paginate(50)
                ->withQueryString();
            $bookingRows = $bookings->getCollection();
            $templateTenantId = $viewer->isSuperAdmin() ? null : $viewer->tenant_id;
            $reminderTemplates = ChatTemplate::query()
                ->where('name', 'like', 'WhatsApp%')
                ->where('tenant_id', $templateTenantId)
                ->orderByDesc('updated_at')
                ->get(['id', 'name']);
            $defaultReminderTemplate = $reminderTemplates->first(function (ChatTemplate $template): bool {
                return str_contains(strtolower($template->name), 'reminder');
            }) ?? $reminderTemplates->first();
            $bookingIds = $bookingRows->pluck('id');
            $reminderHistoryByBooking = $this->bookingListHistoryService->reminderHistoryByBooking($bookingIds);
            $rescheduleHistoryByBooking = $this->bookingListHistoryService->rescheduleHistoryByBooking($bookingIds);
            $bookingFilterCounts = BookingListFilterCounts::fromBookings($bookingRows);
            $showTwoWaySyncInactiveBanner = false;
            if ($viewer->isTenantAdmin() && $viewer->tenant !== null) {
                $viewer->tenant->loadMissing('onboardingState');
                $bookingListMode = $viewer->tenant->onboardingState?->mode
                    ?? TenantOnboardingState::MODE_TWO_WAY_SYNC;
                if ($bookingListMode === TenantOnboardingState::MODE_TWO_WAY_SYNC) {
                    $showTwoWaySyncInactiveBanner = ! $this->onboardingService
                        ->tenantHasConnectedOta($viewer->tenant);
                }
            }
            $allocationRows = BookingResourceAllocation::query()
                ->whereIn('booking_id', $bookingIds)
                ->with('resource:id,name,resource_type,reference_code')
                ->orderBy('allocation_date')
                ->get();
            $allocationsByBookingId = $allocationRows->groupBy('booking_id');
            $bookingAllocationsByBooking = $allocationsByBookingId
                ->map(function ($items): array {
                    return $items->map(function (BookingResourceAllocation $item): array {
                        return [
                            'id' => $item->id,
                            'allocation_date' => optional($item->allocation_date)?->toDateString(),
                            'allocated_units' => $item->allocated_units,
                            'allocated_pax' => $item->allocated_pax,
                            'notes' => $item->notes,
                            'resource_name' => $item->resource?->name,
                            'resource_type' => $item->resource?->resource_type,
                            'resource_code' => $item->resource?->reference_code,
                        ];
                    })->values()->all();
                })
                ->toArray();
            $resourceOptionsQuery = TenantResource::query()
                ->where('status', 'available')
                ->orderBy('tenant_id')
                ->orderBy('resource_type')
                ->orderBy('name');
            if ($viewer->isSuperAdmin()) {
                $resourceOptionsQuery->whereIn('tenant_id', $bookingRows->pluck('tenant_id')->filter()->unique()->values());
            } else {
                $resourceOptionsQuery->where('tenant_id', $viewer->tenant_id);
            }
            $resourceOptions = $resourceOptionsQuery
                ->with('tenant:id,name')
                ->limit(250)
                ->get(['id', 'tenant_id', 'name', 'resource_type', 'reference_code', 'capacity']);

            $bookingAllocationReadinessWarnings = [];
            foreach ($bookingRows as $bookingRow) {
                $gap = $this->allocationRuleService->allocationReadinessMessage(
                    $bookingRow,
                    $allocationsByBookingId->get($bookingRow->id, collect()),
                );
                if ($gap !== null) {
                    $bookingAllocationReadinessWarnings[$bookingRow->id] = $gap;
                }
            }

            return view('apps-bookings', [
                'bookings' => $bookings,
                'bookingStatusCounts' => $bookingFilterCounts['status'],
                'bookingWorkflowCounts' => $bookingFilterCounts['workflow'],
                'showTwoWaySyncInactiveBanner' => $showTwoWaySyncInactiveBanner,
                'reminderTemplates' => $reminderTemplates,
                'defaultReminderTemplateId' => $defaultReminderTemplate?->id,
                'reminderHistoryByBooking' => $reminderHistoryByBooking,
                'rescheduleHistoryByBooking' => $rescheduleHistoryByBooking,
                'bookingAllocationsByBooking' => $bookingAllocationsByBooking,
                'resourceOptions' => $resourceOptions,
                'bookingAllocationReadinessWarnings' => $bookingAllocationReadinessWarnings,
                'canSendReminder' => $viewer->hasTenantPermission('bookings.send_reminder'),
                'canManageReschedule' => $viewer->hasTenantPermission('bookings.manage_reschedule'),
                'canViewRevenue' => $viewer->isAdmin(),
                'canPushGygSync' => $viewer->hasPlatformPermission('platform.travel_agents.sync'),
                'canRetryGygSync' => $viewer->hasPlatformPermission('platform.travel_agents.retry_failed_jobs'),
            ]);
        }

        return abort(404);
    }

    public function root(OnboardingService $onboardingService)
    {
        if (Auth::check()) {
            $user = Auth::user();
            // Tenant_admin yang belum menyelesaikan onboarding mandatory dilempar
            // ke /onboarding (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §4.4).
            // Superadmin (termasuk yang impersonate) tidak terpengaruh.
            if ($onboardingService->shouldForceRedirect($user)) {
                return redirect('/onboarding');
            }

            return redirect()->to('/dashboard-analytics');
        }

        $landingPricingPlans = Cache::remember(
            LandingPricingCache::PUBLIC_PLANS_KEY,
            LandingPricingCache::ttlSeconds(),
            fn () => LandingPricingPlan::allWithFeaturesForDisplay()
        );

        return view('landing', [
            'landingPricingPlans' => $landingPricingPlans,
        ]);
    }

    /*Language Translation*/
    public function lang($locale)
    {
        $allowedLocales = ['en', 'id'];
        if ($locale && in_array($locale, $allowedLocales, true)) {
            App::setLocale($locale);
            Session::put('lang', $locale);
            Session::save();

            return redirect()->back()->with('locale', $locale);
        }

        return redirect()->back();
    }

    public function updateProfile(Request $request, $id)
    {
        $request->validate([
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'email'],
            'avatar' => ['nullable', 'image', 'mimes:jpg,jpeg,png', 'max:1024'],
        ]);

        $user = User::find($id);
        $user->name = $request->get('name');
        $user->email = $request->get('email');

        if ($request->file('avatar')) {
            $avatar = $request->file('avatar');
            $avatarName = time().'.'.$avatar->getClientOriginalExtension();
            $avatarPath = public_path('/images/');
            $avatar->move($avatarPath, $avatarName);
            $user->avatar = $avatarName;
        }

        $user->update();
        if ($user) {
            Session::flash('message', 'User Details Updated successfully!');
            Session::flash('alert-class', 'alert-success');

            // return response()->json([
            //     'isSuccess' => true,
            //     'Message' => "User Details Updated successfully!"
            // ], 200); // Status code here
            return redirect()->back();
        } else {
            Session::flash('message', 'Something went wrong!');
            Session::flash('alert-class', 'alert-danger');

            // return response()->json([
            //     'isSuccess' => true,
            //     'Message' => "Something went wrong!"
            // ], 200); // Status code here
            return redirect()->back();

        }
    }

    public function updatePassword(Request $request, $id)
    {
        $request->validate([
            'current_password' => ['required', 'string'],
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ]);

        if (! (Hash::check($request->get('current_password'), Auth::user()->password))) {
            return response()->json([
                'isSuccess' => false,
                'Message' => 'Your Current password does not matches with the password you provided. Please try again.',
            ], 200); // Status code
        } else {
            $user = User::find($id);
            $user->password = Hash::make($request->get('password'));
            $user->update();
            if ($user) {
                Session::flash('message', 'Password updated successfully!');
                Session::flash('alert-class', 'alert-success');

                return response()->json([
                    'isSuccess' => true,
                    'Message' => 'Password updated successfully!',
                ], 200); // Status code here
            } else {
                Session::flash('message', 'Something went wrong!');
                Session::flash('alert-class', 'alert-danger');

                return response()->json([
                    'isSuccess' => true,
                    'Message' => 'Something went wrong!',
                ], 200); // Status code here
            }
        }
    }
}
