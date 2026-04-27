<?php

namespace App\Policies;

use App\Models\TravelAgent;
use App\Models\User;

class TravelAgentPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPlatformPermission('platform.travel_agents.view');
    }

    public function manageConnection(User $user, TravelAgent $travelAgent): bool
    {
        return $user->hasPlatformPermission('platform.travel_agents.manage_connection');
    }

    public function testConnection(User $user, TravelAgent $travelAgent): bool
    {
        return $user->hasPlatformPermission('platform.travel_agents.test_connection');
    }

    public function sync(User $user, TravelAgent $travelAgent): bool
    {
        return $user->hasPlatformPermission('platform.travel_agents.sync');
    }

    public function retryFailedJobs(User $user, TravelAgent $travelAgent): bool
    {
        return $user->hasPlatformPermission('platform.travel_agents.retry_failed_jobs');
    }
}
