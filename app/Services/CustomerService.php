<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\User;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;

class CustomerService
{
    public function paginate(array $filters, User $viewer): LengthAwarePaginator
    {
        $perPage = max((int) ($filters['per_page'] ?? 15), 1);
        $sortBy = $filters['sort_by'] ?? 'created_at';
        $sortDir = $filters['sort_dir'] ?? 'desc';

        if (! in_array($sortBy, ['created_at', 'full_name', 'email'], true)) {
            $sortBy = 'created_at';
        }

        if (! in_array($sortDir, ['asc', 'desc'], true)) {
            $sortDir = 'desc';
        }

        return Customer::query()
            ->when(! $viewer->isSuperAdmin(), function ($query) use ($viewer): void {
                $query->where(function ($tenantScope) use ($viewer): void {
                    $tenantScope->where('tenant_id', $viewer->tenant_id)->orWhereNull('tenant_id');
                });
            })
            ->when($viewer->isGuide(), function ($query) use ($viewer): void {
                $query->whereHas('bookings', function ($q) use ($viewer): void {
                    $q->visibleToUser($viewer);
                });
            })
            ->withCount([
                'bookings' => function ($q) use ($viewer): void {
                    if (! $viewer->isSuperAdmin()) {
                        $q->visibleToUser($viewer);
                    }
                },
            ])
            ->when($filters['search'] ?? null, function ($query, string $search): void {
                $query->where(function ($nested) use ($search): void {
                    $nested->where('full_name', 'like', '%'.$search.'%')
                        ->orWhere('email', 'like', '%'.$search.'%')
                        ->orWhere('phone', 'like', '%'.$search.'%');
                });
            })
            ->orderBy($sortBy, $sortDir)
            ->paginate($perPage);
    }
}
