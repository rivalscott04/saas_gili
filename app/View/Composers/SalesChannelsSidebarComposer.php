<?php

namespace App\View\Composers;

use App\Models\TravelAgent;
use App\Services\TravelAgentConnectionService;
use Illuminate\Support\Facades\Cache;
use Illuminate\View\View;

class SalesChannelsSidebarComposer
{
    public function __construct(private readonly TravelAgentConnectionService $connectionService)
    {
    }

    public function compose(View $view): void
    {
        $user = auth()->user();
        if ($user === null || ! $user->can('viewAny', TravelAgent::class)) {
            $view->with('sidebarTravelAgents', collect());

            return;
        }

        $this->connectionService->ensureDefaultTravelAgents();

        /** @var array<string, array{label: string, class: string, brand_color: string, image: string|null}> $brandingMap */
        $brandingMap = $this->connectionService->brandingMap();

        $sidebarTtl = max(60, (int) config('performance.sidebar_travel_agents_cache_seconds', 300));

        $sidebarTravelAgents = Cache::remember('sidebar.travel_agents.v1', $sidebarTtl, function () {
            return TravelAgent::query()
                ->where('is_active', true)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(['code', 'name']);
        })->map(static function (TravelAgent $agent) use ($brandingMap): array {
                $code = strtolower((string) $agent->code);
                $branding = $brandingMap[$code] ?? [
                    'label' => strtoupper(substr($code, 0, 2)),
                    'class' => 'bg-light text-secondary',
                    'brand_color' => '#6C757D',
                    'image' => null,
                ];

                return [
                    'code' => $code,
                    'name' => (string) $agent->name,
                    'branding' => $branding,
                ];
            });

        $view->with('sidebarTravelAgents', $sidebarTravelAgents);
    }
}
