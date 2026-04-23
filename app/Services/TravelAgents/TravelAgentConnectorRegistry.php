<?php

namespace App\Services\TravelAgents;

use App\Models\TravelAgent;
use App\Services\TravelAgents\Contracts\TravelAgentConnector;

class TravelAgentConnectorRegistry
{
    public function forAgent(TravelAgent $travelAgent): TravelAgentConnector
    {
        return match ($travelAgent->code) {
            'getyourguide' => app(GetYourGuideConnector::class),
            default => app(GenericTravelAgentConnector::class),
        };
    }
}
