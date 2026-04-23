<?php

namespace App\Services;

use App\Models\TenantAuditLog;
use Carbon\CarbonInterface;

class TenantAuditLogService
{
    /**
     * @param array<string, mixed> $context
     */
    public function record(
        int $tenantId,
        string $eventType,
        ?int $actorUserId = null,
        ?string $entityType = null,
        ?int $entityId = null,
        ?int $tourId = null,
        ?CarbonInterface $serviceDate = null,
        array $context = []
    ): TenantAuditLog {
        return TenantAuditLog::query()->create([
            'tenant_id' => $tenantId,
            'actor_user_id' => $actorUserId,
            'event_type' => $eventType,
            'entity_type' => $entityType,
            'entity_id' => $entityId,
            'tour_id' => $tourId,
            'service_date' => $serviceDate?->toDateString(),
            'context' => $context,
            'occurred_at' => now(),
        ]);
    }
}
