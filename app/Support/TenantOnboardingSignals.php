<?php

namespace App\Support;

/**
 * Snapshot of tenant onboarding-related flags (built from a small number of SQL round-trips).
 */
final class TenantOnboardingSignals
{
    public function __construct(
        public readonly bool $hasActiveTour,
        public readonly bool $hasTourCapacityOrDefault,
        public readonly bool $hasResource,
        public readonly bool $hasActiveOta,
        public readonly bool $hasSecondUser,
        public readonly bool $hasManualBooking,
        public readonly bool $hasMagicLinkSent,
        public readonly bool $hasOutboundSyncSucceeded,
        public readonly bool $hasWhatsAppTemplateWithMagicLink,
    ) {
    }
}
