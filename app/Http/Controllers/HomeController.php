<?php

namespace App\Http\Controllers;

use App\Models\LandingPricingPlan;
use App\Models\Booking;
use App\Models\BookingReschedule;
use App\Models\BookingResourceAllocation;
use App\Models\BookingStatusEvent;
use App\Models\ChatTemplate;
use App\Models\Tenant;
use App\Models\TenantResource;
use App\Models\User;
use App\Services\DashboardService;
use App\Services\TourAllocationRuleService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\App;
use Illuminate\Support\Facades\Session;
use Illuminate\Support\Carbon;

class HomeController extends Controller
{
    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct(private readonly DashboardService $dashboardService)
    {
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
                $tenantOptions = Tenant::query()
                    ->orderBy('name')
                    ->get(['id', 'name', 'code']);
                $requestedTenantCode = trim((string) $request->query('tenant', ''));
                if ($requestedTenantCode !== '') {
                    $selectedTenantId = $tenantOptions->first(
                        fn (Tenant $candidate): bool => strtolower((string) $candidate->code) === strtolower($requestedTenantCode)
                    )?->id;
                }
            }

            return view('dashboard-analytics', [
                'summary' => $this->dashboardService->summary($viewer, $selectedTenantId),
                'urgentBookings' => $this->dashboardService->urgentBookings($viewer, 5, $selectedTenantId),
                'recentBookings' => $this->dashboardService->recentBookings($viewer, 8, $selectedTenantId),
                'tenantOptions' => $tenantOptions,
                'selectedTenantId' => $selectedTenantId,
                'selectedTenantCode' => $selectedTenantId
                    ? (string) optional($tenantOptions->firstWhere('id', $selectedTenantId))->code
                    : '',
                'isSuperAdminViewer' => $viewer?->isSuperAdmin() ?? false,
                'canViewRevenue' => $viewer?->isAdmin() ?? false,
            ]);
        }

        if ($request->path() === 'apps-bookings-calendar') {
            $viewer = $request->user();
            if (! $viewer || ! $viewer->hasTenantPermission('bookings.view')) {
                return abort(403);
            }
            $bookings = Booking::query()
                ->visibleToUser($viewer)
                ->with('customer')
                ->whereNotNull('tour_start_at')
                ->orderBy('tour_start_at')
                ->limit(300)
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
                    "CASE
                        WHEN tour_start_at IS NULL THEN 3
                        WHEN tour_start_at < ? THEN 2
                        WHEN tour_start_at BETWEEN ? AND ? THEN 0
                        ELSE 1
                    END ASC",
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
            $reminderHistoryByBooking = BookingStatusEvent::query()
                ->whereIn('booking_id', $bookingIds)
                ->where('reason', 'reminder_sent')
                ->orderByDesc('created_at')
                ->get(['booking_id', 'created_at', 'metadata'])
                ->groupBy('booking_id')
                ->map(function ($events): array {
                    return $events->take(5)->map(function (BookingStatusEvent $event): array {
                        return [
                            'sent_at' => optional($event->created_at)->toIso8601String(),
                            'template_name' => (string) data_get($event->metadata, 'template_name', '-'),
                            'sent_to_phone' => (string) data_get($event->metadata, 'sent_to_phone', '-'),
                        ];
                    })->values()->all();
                })
                ->toArray();
            $rescheduleHistoryByBooking = BookingReschedule::query()
                ->whereIn('booking_id', $bookingIds)
                ->orderByDesc('created_at')
                ->get([
                    'id',
                    'booking_id',
                    'requested_by',
                    'request_source',
                    'workflow_status',
                    'old_tour_start_at',
                    'requested_tour_start_at',
                    'final_tour_start_at',
                    'requested_reason',
                    'notes',
                    'reviewed_at',
                    'completed_at',
                    'created_at',
                ])
                ->groupBy('booking_id')
                ->map(function ($items): array {
                    return $items->take(8)->map(function (BookingReschedule $item): array {
                        return [
                            'id' => $item->id,
                            'requested_by' => $item->requested_by,
                            'request_source' => $item->request_source,
                            'workflow_status' => $item->workflow_status,
                            'old_tour_start_at' => optional($item->old_tour_start_at)->toIso8601String(),
                            'requested_tour_start_at' => optional($item->requested_tour_start_at)->toIso8601String(),
                            'final_tour_start_at' => optional($item->final_tour_start_at)->toIso8601String(),
                            'requested_reason' => $item->requested_reason,
                            'notes' => $item->notes,
                            'reviewed_at' => optional($item->reviewed_at)->toIso8601String(),
                            'completed_at' => optional($item->completed_at)->toIso8601String(),
                            'created_at' => optional($item->created_at)->toIso8601String(),
                        ];
                    })->values()->all();
                })
                ->toArray();
            $bookingAllocationsByBooking = BookingResourceAllocation::query()
                ->whereIn('booking_id', $bookingIds)
                ->with('resource:id,name,resource_type,reference_code')
                ->orderBy('allocation_date')
                ->get()
                ->groupBy('booking_id')
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
                ->get(['id', 'tenant_id', 'name', 'resource_type', 'reference_code', 'capacity']);

            $allocationRuleService = app(TourAllocationRuleService::class);
            $bookingAllocationReadinessWarnings = [];
            foreach ($bookingRows as $bookingRow) {
                $gap = $allocationRuleService->allocationReadinessMessage($bookingRow);
                if ($gap !== null) {
                    $bookingAllocationReadinessWarnings[$bookingRow->id] = $gap;
                }
            }

            return view('apps-bookings', [
                'bookings' => $bookings,
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

        if (view()->exists($request->path())) {
            return view($request->path());
        }
        return abort(404);
    }

    public function root()
    {
        if (Auth::check()) {
            return redirect()->to(Auth::user()->isAdmin() ? '/dashboard-analytics' : '/dashboard-analytics');
        }

        $landingPricingPlans = LandingPricingPlan::allWithFeaturesForDisplay();

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
            $avatarName = time() . '.' . $avatar->getClientOriginalExtension();
            $avatarPath = public_path('/images/');
            $avatar->move($avatarPath, $avatarName);
            $user->avatar =  $avatarName;
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

        if (!(Hash::check($request->get('current_password'), Auth::user()->password))) {
            return response()->json([
                'isSuccess' => false,
                'Message' => "Your Current password does not matches with the password you provided. Please try again."
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
                    'Message' => "Password updated successfully!"
                ], 200); // Status code here
            } else {
                Session::flash('message', 'Something went wrong!');
                Session::flash('alert-class', 'alert-danger');
                return response()->json([
                    'isSuccess' => true,
                    'Message' => "Something went wrong!"
                ], 200); // Status code here
            }
        }
    }
}
