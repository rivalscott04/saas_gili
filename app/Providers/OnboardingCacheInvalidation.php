<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ChatTemplate;
use App\Models\Tenant;
use App\Models\TenantResource;
use App\Models\TenantTravelAgentConnection;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use App\Models\User;
use App\Services\OnboardingService;
use Illuminate\Support\ServiceProvider;

/**
 * Flush onboarding summary / mandatory-complete caches when tenant setup data changes.
 */
class OnboardingCacheInvalidation extends ServiceProvider
{
    public function boot(): void
    {
        $flush = static function (?int $tenantId): void {
            if ($tenantId !== null && $tenantId > 0) {
                app(OnboardingService::class)->flushSummaryCache($tenantId);
            }
        };

        Tenant::saved(static fn (Tenant $tenant) => $flush((int) $tenant->id));

        Tour::saved(static fn (Tour $tour) => $flush((int) $tour->tenant_id));
        Tour::deleted(static fn (Tour $tour) => $flush((int) $tour->tenant_id));

        TourDayCapacity::saved(static fn (TourDayCapacity $capacity) => $flush((int) $capacity->tenant_id));
        TourDayCapacity::deleted(static fn (TourDayCapacity $capacity) => $flush((int) $capacity->tenant_id));

        TenantResource::saved(static fn (TenantResource $resource) => $flush((int) $resource->tenant_id));
        TenantResource::deleted(static fn (TenantResource $resource) => $flush((int) $resource->tenant_id));

        ChatTemplate::saved(static fn (ChatTemplate $template) => $flush($template->tenant_id ? (int) $template->tenant_id : null));
        ChatTemplate::deleted(static fn (ChatTemplate $template) => $flush($template->tenant_id ? (int) $template->tenant_id : null));

        User::saved(static fn (User $user) => $flush($user->tenant_id ? (int) $user->tenant_id : null));
        User::deleted(static fn (User $user) => $flush($user->tenant_id ? (int) $user->tenant_id : null));

        TenantTravelAgentConnection::saved(static fn (TenantTravelAgentConnection $c) => $flush((int) $c->tenant_id));
        TenantTravelAgentConnection::deleted(static fn (TenantTravelAgentConnection $c) => $flush((int) $c->tenant_id));

        Booking::saved(static fn (Booking $booking) => $flush((int) $booking->tenant_id));
        Booking::deleted(static fn (Booking $booking) => $flush((int) $booking->tenant_id));

        BookingStatusEvent::saved(static function (BookingStatusEvent $event) use ($flush): void {
            if ($event->relationLoaded('booking') && $event->booking !== null) {
                $flush((int) $event->booking->tenant_id);

                return;
            }

            $flush($event->booking_id ? (int) Booking::query()->whereKey($event->booking_id)->value('tenant_id') : null);
        });
    }
}
