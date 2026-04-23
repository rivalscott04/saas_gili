<?php

namespace App\Services;

use App\Models\ChatTemplate;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class ChatTemplateService
{
    /**
     * @var array<int, array{name: string, content: string}>
     */
    private const DEFAULT_TEMPLATES = [
        [
            'name' => 'Booking Reminder',
            'content' => '{{greeting}} {{customerName}}! Friendly reminder: you already have {{tourName}} booked. Please let us know if you’re still joining us on the day, or if anything changed. Thanks!',
        ],
        [
            'name' => 'Thank You',
            'content' => '{{greeting}} {{customerName}}, thanks for choosing us! We hope you enjoyed the {{tourName}}.',
        ],
        [
            'name' => 'Payment Request',
            'content' => '{{greeting}} {{customerName}}, please kindly complete the payment for your {{tourName}} booking at your earliest convenience.',
        ],
    ];

    public function paginate(User $viewer, int $perPage = 15): LengthAwarePaginator
    {
        $this->ensureDefaults($viewer);

        return ChatTemplate::query()
            ->when(! $viewer->isSuperAdmin(), function ($query) use ($viewer): void {
                $query->where(function ($tenantScope) use ($viewer): void {
                    $tenantScope->where('tenant_id', $viewer->tenant_id)->orWhereNull('tenant_id');
                });
            })
            ->when($viewer->isGuide(), function ($query) use ($viewer): void {
                $query->where(function ($q) use ($viewer): void {
                    $q->whereNull('user_id')->orWhere('user_id', $viewer->id);
                });
            })
            ->latest()
            ->paginate($perPage);
    }

    private function ensureDefaults(User $viewer): void
    {
        if (ChatTemplate::query()->where('tenant_id', $viewer->tenant_id)->exists()) {
            return;
        }

        ChatTemplate::query()->insert(
            array_map(
                fn (array $template): array => [
                    'tenant_id' => $viewer->tenant_id,
                    'name' => $template['name'],
                    'content' => $template['content'],
                    'created_at' => now(),
                    'updated_at' => now(),
                ],
                self::DEFAULT_TEMPLATES
            )
        );
    }
}
