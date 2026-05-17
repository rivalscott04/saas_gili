<?php

namespace App\Services;

use App\Models\Booking;
use App\Models\BookingStatusEvent;
use App\Models\ChatTemplate;
use App\Models\Tenant;
use App\Models\TenantOnboardingState;
use App\Models\TenantResource;
use App\Models\TenantTravelAgentConnection;
use App\Models\Tour;
use App\Models\TourDayCapacity;
use App\Models\User;
use App\Support\SuperAdminImpersonation;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;

/**
 * Backbone untuk fitur "Mulai dari sini" (Setup Checklist).
 *
 * Status tiap step TIDAK disimpan sebagai boolean — selalu dihitung on the fly
 * dari data nyata milik tenant (docs/ux-review/2026-05-14-tenant-onboarding-plan.md §4.3).
 * step_completed_at di tabel tenant_onboarding_states hanya menyimpan timestamp
 * pertama kali tiap step transit ke "done" untuk keperluan audit/analitik.
 */
class OnboardingService
{
    /**
     * Per-request cache: summaryFor() runs many exists() checks per tenant.
     *
     * @var array<int, list<array{
     *     key: string,
     *     mandatory: bool,
     *     done: bool,
     *     hidden: bool,
     *     href: string,
     *     title_key: string,
     * }>>
     */
    private array $summaryCache = [];

    public function flushSummaryCache(?int $tenantId = null): void
    {
        if ($tenantId === null) {
            $this->summaryCache = [];

            return;
        }

        $prefix = $tenantId.':';
        foreach (array_keys($this->summaryCache) as $cacheKey) {
            if (str_starts_with((string) $cacheKey, $prefix)) {
                unset($this->summaryCache[$cacheKey]);
            }
        }

        foreach ([TenantOnboardingState::MODE_TWO_WAY_SYNC, TenantOnboardingState::MODE_APP_ONLY] as $mode) {
            Cache::forget('onboarding.summary.v1.'.$tenantId.'.'.$mode);
        }
    }

    /**
     * Step yang wajib selesai supaya tenant_admin tidak lagi dipaksa ke /onboarding.
     *
     * @var array<int, string>
     */
    public const MANDATORY_STEPS = [
        'tenant_profile',
        'first_tour',
        'tour_capacity_or_default',
        'first_resource',
        'wa_template',
    ];

    /**
     * Routes tenant_admin may hit while mandatory onboarding is incomplete.
     *
     * @var array<int, string>
     */
    public const ALLOWED_ROUTES_WHILE_GATED = [
        'onboarding.index',
        'onboarding.mode',
        'onboarding.dismiss',
        'tenant-profile.edit',
        'tenant-profile.update',
        'tours.index',
        'tours.store',
        'tours.update',
        'tours.archive',
        'tour-day-capacities.index',
        'tour-day-capacities.store',
        'tour-day-capacities.destroy',
        'operations-resources.index',
        'operations-resources.store',
        'operations-resources.update',
        'operations-resources.destroy',
        'operations-resources.block-out',
        'whatsapp-template-message.index',
        'whatsapp-template-message.update',
        'whatsapp-template-message.destroy',
        'logout',
        'bookings.manual.create',
        'bookings.manual.store',
        'travel-agents.index',
    ];

    /**
     * Catch-all / legacy paths allowed while mandatory onboarding is incomplete.
     *
     * @var array<int, string>
     */
    public const ALLOWED_PATHS_WHILE_GATED = [
        'apps-bookings',
        'apps-bookings-calendar',
        'apps-bookings-manual-create',
    ];

    public static function isRouteAllowedWhileGated(?string $routeName, string $path): bool
    {
        if ($routeName !== null && in_array($routeName, self::ALLOWED_ROUTES_WHILE_GATED, true)) {
            return true;
        }

        return in_array(trim($path, '/'), self::ALLOWED_PATHS_WHILE_GATED, true);
    }

    /**
     * Placeholder yang harus ada di template WA supaya step wa_template dianggap done.
     * Kalau template tidak memuat salah satu dari placeholder ini, pesan yang
     * dikirim ke customer tidak menyertakan link konfirmasi.
     *
     * @var array<int, string>
     */
    private const MAGIC_LINK_PLACEHOLDERS = [
        '{magic_link}',
        '{link}',
        '{magic-link}',
        '{magiclink}',
    ];

    /**
     * Return ringkasan tiap step beserta status (done/pending), apakah wajib,
     * deeplink action, dan flag visibility (untuk step yang conditional).
     *
     * @return array<int, array{
     *     key: string,
     *     mandatory: bool,
     *     done: bool,
     *     hidden: bool,
     *     href: string,
     *     title_key: string,
     * }>
     */
    public function summaryFor(Tenant $tenant): array
    {
        $tenantId = (int) $tenant->getKey();
        $tenant->unsetRelation('onboardingState');
        $tenant->load('onboardingState');
        $mode = $tenant->onboardingState?->mode ?? TenantOnboardingState::MODE_TWO_WAY_SYNC;
        $cacheKey = $this->summaryCacheKey($tenantId, $mode);

        if ($this->shouldUseSummaryCache() && array_key_exists($cacheKey, $this->summaryCache)) {
            return $this->summaryCache[$cacheKey];
        }

        if (! app()->runningUnitTests()) {
            $persistentKey = 'onboarding.summary.v1.'.$tenantId.'.'.$mode;
            $ttl = max(30, (int) config('performance.onboarding_summary_cache_seconds', 90));
            $steps = Cache::remember($persistentKey, $ttl, fn (): array => $this->buildSummarySteps($tenant));

            if ($this->shouldUseSummaryCache()) {
                $this->summaryCache[$cacheKey] = $steps;
            }

            return $steps;
        }

        $steps = $this->buildSummarySteps($tenant);

        if ($this->shouldUseSummaryCache()) {
            $this->summaryCache[$cacheKey] = $steps;
        }

        return $steps;
    }

    public function tenantHasConnectedOta(Tenant $tenant): bool
    {
        return $this->hasActiveTravelAgent($tenant);
    }

    /**
     * @return list<array{
     *     key: string,
     *     mandatory: bool,
     *     done: bool,
     *     hidden: bool,
     *     href: string,
     *     title_key: string,
     * }>
     */
    private function buildSummarySteps(Tenant $tenant): array
    {
        $tenant->loadMissing('onboardingState');
        $mode = $tenant->onboardingState?->mode ?? TenantOnboardingState::MODE_TWO_WAY_SYNC;
        $hasActiveOta = $this->hasActiveTravelAgent($tenant);
        $isTwoWaySync = $mode === TenantOnboardingState::MODE_TWO_WAY_SYNC;

        return [
            [
                'key' => 'tenant_profile',
                'mandatory' => true,
                'done' => $this->isProfileComplete($tenant),
                'hidden' => false,
                'href' => route('tenant-profile.edit'),
                'title_key' => 'translation.onboarding-step-tenant-profile',
            ],
            [
                'key' => 'first_tour',
                'mandatory' => true,
                'done' => Tour::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('is_active', true)
                    ->exists(),
                'hidden' => false,
                'href' => route('tours.index'),
                'title_key' => 'translation.onboarding-step-first-tour',
            ],
            [
                'key' => 'tour_capacity_or_default',
                'mandatory' => true,
                'done' => $this->hasTourCapacityOrDefault($tenant),
                'hidden' => false,
                'href' => route('tour-day-capacities.index'),
                'title_key' => 'translation.onboarding-step-tour-capacity',
            ],
            [
                'key' => 'first_resource',
                'mandatory' => true,
                'done' => TenantResource::query()
                    ->where('tenant_id', $tenant->id)
                    ->exists(),
                'hidden' => false,
                'href' => route('operations-resources.index'),
                'title_key' => 'translation.onboarding-step-first-resource',
            ],
            [
                'key' => 'wa_template',
                'mandatory' => true,
                'done' => $this->hasWhatsAppTemplateWithMagicLink($tenant),
                'hidden' => false,
                'href' => url('apps-whatsapp-template-message'),
                'title_key' => 'translation.onboarding-step-wa-template',
            ],
            [
                'key' => 'connect_ota',
                'mandatory' => false,
                'done' => $hasActiveOta,
                // Mode app_only: sembunyikan step yang berkaitan dengan OTA.
                'hidden' => ! $isTwoWaySync,
                'href' => route('travel-agents.index'),
                'title_key' => 'translation.onboarding-step-connect-ota',
            ],
            [
                'key' => 'invite_team',
                'mandatory' => false,
                'done' => User::query()
                    ->where('tenant_id', $tenant->id)
                    ->count() >= 2,
                'hidden' => false,
                'href' => route('tenant-users.index'),
                'title_key' => 'translation.onboarding-step-invite-team',
            ],
            [
                'key' => 'first_app_booking_drill',
                'mandatory' => false,
                'done' => Booking::query()
                    ->where('tenant_id', $tenant->id)
                    ->where('booking_source', 'manual')
                    ->exists(),
                'hidden' => false,
                'href' => route('bookings.manual.create'),
                'title_key' => 'translation.onboarding-step-first-app-booking',
            ],
            [
                'key' => 'first_magic_link_sent',
                'mandatory' => false,
                'done' => $this->hasMagicLinkBeenSent($tenant),
                'hidden' => false,
                'href' => url('apps-bookings'),
                'title_key' => 'translation.onboarding-step-first-magic-link',
            ],
            [
                'key' => 'first_outbound_sync_ok',
                'mandatory' => false,
                'done' => $this->hasOutboundSyncSucceeded($tenant),
                // Hanya muncul kalau tenant punya channel OTA aktif (mode two_way_sync + sudah connect).
                'hidden' => ! $isTwoWaySync || ! $hasActiveOta,
                'href' => url('apps-bookings'),
                'title_key' => 'translation.onboarding-step-first-outbound-sync',
            ],
        ];
    }

    public function mandatoryCompleted(Tenant $tenant): int
    {
        $done = 0;
        foreach ($this->summaryFor($tenant) as $step) {
            if ($step['mandatory'] && $step['done']) {
                $done++;
            }
        }

        return $done;
    }

    public function mandatoryTotal(): int
    {
        return count(self::MANDATORY_STEPS);
    }

    public function isAllMandatoryDone(Tenant $tenant): bool
    {
        return $this->mandatoryCompleted($tenant) >= $this->mandatoryTotal();
    }

    /**
     * Shared UI flags for sidebar + dashboard widget (one summaryFor() per request).
     *
     * @return array{
     *     show_nav_link: bool,
     *     show_dashboard_widget: bool,
     *     mandatory_done: int,
     *     mandatory_total: int
     * }
     */
    public function uiStateFor(Tenant $tenant): array
    {
        $mandatoryDone = $this->mandatoryCompleted($tenant);
        $mandatoryTotal = $this->mandatoryTotal();
        $isDismissed = $tenant->onboardingState?->dismissed_at !== null;
        $incomplete = ! $isDismissed && $mandatoryDone < $mandatoryTotal;

        return [
            'show_nav_link' => $incomplete,
            'show_dashboard_widget' => $incomplete,
            'mandatory_done' => $mandatoryDone,
            'mandatory_total' => $mandatoryTotal,
        ];
    }

    /**
     * Apakah user ini harus dipaksa redirect ke /onboarding saat masuk root /.
     *
     * Lihat docs/ux-review/2026-05-14-tenant-onboarding-plan.md §4.4 dan §13.3:
     * - Hanya tenant_admin (bukan superadmin, bukan guide).
     * - Tidak saat sesi impersonate (superadmin yang menyamar).
     * - Tidak kalau semua step wajib sudah done.
     * - dismissed_at hanya menyembunyikan widget beranda/sidebar (uiStateFor), bukan melepaskan gate.
     */
    public function shouldForceRedirect(?User $user): bool
    {
        if ($user === null) {
            return false;
        }

        if ($user->isSuperAdmin()) {
            return false;
        }

        if (! $user->isTenantAdmin()) {
            return false;
        }

        if (SuperAdminImpersonation::isImpersonating()) {
            return false;
        }

        $tenant = $user->tenant;
        if ($tenant === null) {
            return false;
        }

        if ($this->isAllMandatoryDone($tenant)) {
            return false;
        }

        return true;
    }

    public function setMode(Tenant $tenant, string $mode): TenantOnboardingState
    {
        if (! in_array($mode, TenantOnboardingState::ALLOWED_MODES, true)) {
            throw new \InvalidArgumentException("Unsupported onboarding mode: {$mode}");
        }

        $state = TenantOnboardingState::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['mode' => $mode],
        );
        $this->flushSummaryCache((int) $tenant->id);
        $tenant->unsetRelation('onboardingState');

        return $state;
    }

    public function dismiss(Tenant $tenant): TenantOnboardingState
    {
        return TenantOnboardingState::updateOrCreate(
            ['tenant_id' => $tenant->id],
            ['dismissed_at' => Carbon::now()],
        );
    }

    /**
     * Catat (idempotent) timestamp pertama kali tiap step transit ke "done".
     * Aman dipanggil setiap render — hanya menulis kalau ada perubahan baru.
     */
    public function snapshotCompletedSteps(Tenant $tenant): void
    {
        $debounceKey = 'onboarding.snapshot.debounce.'.$tenant->id;
        $debounceSeconds = max(60, (int) config('performance.onboarding_snapshot_debounce_seconds', 300));
        if (Cache::has($debounceKey)) {
            return;
        }

        $state = TenantOnboardingState::firstOrNew(['tenant_id' => $tenant->id]);
        $existing = $state->step_completed_at ?? [];
        $now = Carbon::now()->toIso8601String();
        $changed = false;

        foreach ($this->summaryFor($tenant) as $step) {
            if (! $step['done'] || $step['hidden']) {
                continue;
            }
            if (! isset($existing[$step['key']])) {
                $existing[$step['key']] = $now;
                $changed = true;
            }
        }

        if ($changed) {
            $state->step_completed_at = $existing;
            $state->save();
        }

        Cache::put($debounceKey, true, $debounceSeconds);
    }

    private function shouldUseSummaryCache(): bool
    {
        if (app()->runningUnitTests()) {
            return false;
        }

        return spl_object_hash(request()) !== '';
    }

    private function summaryCacheKey(int $tenantId, string $mode): string
    {
        return spl_object_hash(request()).':'.$tenantId.':'.$mode;
    }

    private function isProfileComplete(Tenant $tenant): bool
    {
        // whatsapp_sender_number wajib karena dipakai sebagai pengirim magic link WA.
        return filled($tenant->name) && filled($tenant->whatsapp_sender_number ?? null);
    }

    private function hasTourCapacityOrDefault(Tenant $tenant): bool
    {
        // "Default daily quota" di kode = kolom `tours.default_max_pax_per_day`.
        $hasDefault = Tour::query()
            ->where('tenant_id', $tenant->id)
            ->whereNotNull('default_max_pax_per_day')
            ->where('default_max_pax_per_day', '>', 0)
            ->exists();
        if ($hasDefault) {
            return true;
        }

        return TourDayCapacity::query()
            ->whereHas('tour', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->exists();
    }

    private function hasWhatsAppTemplateWithMagicLink(Tenant $tenant): bool
    {
        $templates = ChatTemplate::query()
            ->where('tenant_id', $tenant->id)
            ->where('name', 'like', 'WhatsApp%')
            ->pluck('content');

        foreach ($templates as $content) {
            $lower = strtolower((string) $content);
            foreach (self::MAGIC_LINK_PLACEHOLDERS as $placeholder) {
                if (str_contains($lower, strtolower($placeholder))) {
                    return true;
                }
            }
        }

        return false;
    }

    private function hasActiveTravelAgent(Tenant $tenant): bool
    {
        return TenantTravelAgentConnection::query()
            ->where('tenant_id', $tenant->id)
            ->where('status', 'connected')
            ->exists();
    }

    private function hasMagicLinkBeenSent(Tenant $tenant): bool
    {
        return BookingStatusEvent::query()
            ->whereHas('booking', fn ($q) => $q->where('tenant_id', $tenant->id))
            ->where('reason', 'reminder_sent')
            ->exists();
    }

    private function hasOutboundSyncSucceeded(Tenant $tenant): bool
    {
        return Booking::query()
            ->where('tenant_id', $tenant->id)
            ->where('booking_source', 'manual')
            ->whereNotNull('external_booking_ref')
            ->where('sync_status', 'success')
            ->exists();
    }
}
