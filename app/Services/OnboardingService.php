<?php

namespace App\Services;

use App\Models\ChatTemplate;
use App\Models\Tenant;
use App\Models\TenantOnboardingState;
use App\Models\User;
use App\Support\SuperAdminImpersonation;
use App\Support\TenantOnboardingSignals;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\DB;

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

        Cache::forget($this->mandatoryCompleteCacheKey($tenantId));
        Cache::forget($this->connectedOtaCacheKey($tenantId));
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
        $tenantId = (int) $tenant->id;

        if (app()->runningUnitTests()) {
            return $this->resolveTenantSignals($tenant)->hasActiveOta;
        }

        $ttl = max(30, (int) config('performance.onboarding_summary_cache_seconds', 90));

        return (bool) Cache::remember(
            $this->connectedOtaCacheKey($tenantId),
            $ttl,
            fn (): bool => $this->resolveTenantSignals($tenant)->hasActiveOta,
        );
    }

    public function isMandatoryCompleteCached(Tenant $tenant): bool
    {
        if (app()->runningUnitTests()) {
            return $this->isAllMandatoryDone($tenant);
        }

        return Cache::get($this->mandatoryCompleteCacheKey((int) $tenant->id)) === true;
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
        $signals = $this->resolveTenantSignals($tenant);
        $hasActiveOta = $signals->hasActiveOta;
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
                'done' => $signals->hasActiveTour,
                'hidden' => false,
                'href' => route('tours.index'),
                'title_key' => 'translation.onboarding-step-first-tour',
            ],
            [
                'key' => 'tour_capacity_or_default',
                'mandatory' => true,
                'done' => $signals->hasTourCapacityOrDefault,
                'hidden' => false,
                'href' => $this->hrefWithPageTour(route('tour-day-capacities.index')),
                'title_key' => 'translation.onboarding-step-tour-capacity',
            ],
            [
                'key' => 'first_resource',
                'mandatory' => true,
                'done' => $signals->hasResource,
                'hidden' => false,
                'href' => route('operations-resources.index'),
                'title_key' => 'translation.onboarding-step-first-resource',
            ],
            [
                'key' => 'wa_template',
                'mandatory' => true,
                'done' => $signals->hasWhatsAppTemplateWithMagicLink,
                'hidden' => false,
                'href' => $this->hrefWithPageTour(url('apps-whatsapp-template-message')),
                'title_key' => 'translation.onboarding-step-wa-template',
            ],
            [
                'key' => 'connect_ota',
                'mandatory' => false,
                'done' => $hasActiveOta,
                'hidden' => ! $isTwoWaySync,
                'href' => route('travel-agents.index'),
                'title_key' => 'translation.onboarding-step-connect-ota',
            ],
            [
                'key' => 'invite_team',
                'mandatory' => false,
                'done' => $signals->hasSecondUser,
                'hidden' => false,
                'href' => route('tenant-users.index'),
                'title_key' => 'translation.onboarding-step-invite-team',
            ],
            [
                'key' => 'first_app_booking_drill',
                'mandatory' => false,
                'done' => $signals->hasManualBooking,
                'hidden' => false,
                'href' => $this->hrefWithPageTour(route('bookings.manual.create')),
                'title_key' => 'translation.onboarding-step-first-app-booking',
            ],
            [
                'key' => 'first_magic_link_sent',
                'mandatory' => false,
                'done' => $signals->hasMagicLinkSent,
                'hidden' => false,
                'href' => $this->hrefWithPageTour(url('apps-bookings')),
                'title_key' => 'translation.onboarding-step-first-magic-link',
            ],
            [
                'key' => 'first_outbound_sync_ok',
                'mandatory' => false,
                'done' => $signals->hasOutboundSyncSucceeded,
                'hidden' => ! $isTwoWaySync || ! $hasActiveOta,
                'href' => $this->hrefWithPageTour(url('apps-bookings')),
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
        $tenantId = (int) $tenant->id;

        if (! app()->runningUnitTests()) {
            $cacheKey = $this->mandatoryCompleteCacheKey($tenantId);
            $cached = Cache::get($cacheKey);
            if ($cached === true) {
                return true;
            }
        }

        $done = $this->mandatoryCompleted($tenant) >= $this->mandatoryTotal();

        if ($done && ! app()->runningUnitTests()) {
            $ttl = max(300, (int) config('performance.onboarding_mandatory_complete_cache_seconds', 86400));
            Cache::put($this->mandatoryCompleteCacheKey($tenantId), true, $ttl);
        }

        return $done;
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
        $tenant->loadMissing('onboardingState');
        $mandatoryTotal = $this->mandatoryTotal();

        if ($this->isMandatoryCompleteCached($tenant)) {
            return [
                'show_nav_link' => false,
                'show_dashboard_widget' => false,
                'mandatory_done' => $mandatoryTotal,
                'mandatory_total' => $mandatoryTotal,
            ];
        }

        $mandatoryDone = $this->mandatoryCompleted($tenant);
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

        if ($this->isMandatoryCompleteCached($tenant) || $this->isAllMandatoryDone($tenant)) {
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

    /**
     * Trigger Shepherd page tour on first visit from Setup Checklist deeplinks.
     */
    private function hrefWithPageTour(string $url): string
    {
        $separator = str_contains($url, '?') ? '&' : '?';

        return $url.$separator.'onboarding_tour=1';
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

    private function mandatoryCompleteCacheKey(int $tenantId): string
    {
        return 'onboarding.mandatory_complete.v1.'.$tenantId;
    }

    private function connectedOtaCacheKey(int $tenantId): string
    {
        return 'tenant.has_connected_ota.v1.'.$tenantId;
    }

    /**
     * Batch onboarding flags into two SQL round-trips (aggregate + WA templates).
     */
    private function resolveTenantSignals(Tenant $tenant): TenantOnboardingSignals
    {
        $tenantId = (int) $tenant->id;

        $row = DB::selectOne(
            'SELECT
                EXISTS(SELECT 1 FROM tours WHERE tenant_id = ? AND is_active = 1) AS has_active_tour,
                EXISTS(
                    SELECT 1 FROM tours
                    WHERE tenant_id = ?
                      AND default_max_pax_per_day IS NOT NULL
                      AND default_max_pax_per_day > 0
                ) AS has_default_capacity,
                EXISTS(
                    SELECT 1 FROM tour_day_capacities AS tdc
                    INNER JOIN tours AS t ON t.id = tdc.tour_id
                    WHERE t.tenant_id = ?
                ) AS has_capacity_override,
                EXISTS(SELECT 1 FROM tenant_resources WHERE tenant_id = ?) AS has_resource,
                EXISTS(
                    SELECT 1 FROM tenant_travel_agent_connections
                    WHERE tenant_id = ? AND status = ?
                ) AS has_active_ota,
                (
                    SELECT COUNT(*) FROM (
                        SELECT id FROM users WHERE tenant_id = ? LIMIT 2
                    ) AS team_probe
                ) >= 2 AS has_second_user,
                EXISTS(
                    SELECT 1 FROM bookings WHERE tenant_id = ? AND booking_source = ?
                ) AS has_manual_booking,
                EXISTS(
                    SELECT 1 FROM booking_status_events AS bse
                    INNER JOIN bookings AS b ON b.id = bse.booking_id
                    WHERE b.tenant_id = ? AND bse.reason = ?
                ) AS has_magic_link_sent,
                EXISTS(
                    SELECT 1 FROM bookings
                    WHERE tenant_id = ?
                      AND booking_source = ?
                      AND external_booking_ref IS NOT NULL
                      AND sync_status = ?
                ) AS has_outbound_sync',
            [
                $tenantId,
                $tenantId,
                $tenantId,
                $tenantId,
                $tenantId,
                'connected',
                $tenantId,
                $tenantId,
                'manual',
                $tenantId,
                'reminder_sent',
                $tenantId,
                'manual',
                'success',
            ],
        );

        return new TenantOnboardingSignals(
            hasActiveTour: (bool) $row->has_active_tour,
            hasTourCapacityOrDefault: ((bool) $row->has_default_capacity) || ((bool) $row->has_capacity_override),
            hasResource: (bool) $row->has_resource,
            hasActiveOta: (bool) $row->has_active_ota,
            hasSecondUser: (bool) $row->has_second_user,
            hasManualBooking: (bool) $row->has_manual_booking,
            hasMagicLinkSent: (bool) $row->has_magic_link_sent,
            hasOutboundSyncSucceeded: (bool) $row->has_outbound_sync,
            hasWhatsAppTemplateWithMagicLink: $this->detectWhatsAppMagicLinkTemplate($tenantId),
        );
    }

    private function detectWhatsAppMagicLinkTemplate(int $tenantId): bool
    {
        $templates = ChatTemplate::query()
            ->where('tenant_id', $tenantId)
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
}
