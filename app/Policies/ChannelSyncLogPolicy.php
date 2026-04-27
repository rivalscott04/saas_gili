<?php

namespace App\Policies;

use App\Models\User;

class ChannelSyncLogPolicy
{
    public function viewAny(User $user): bool
    {
        return $user->hasPlatformPermission('platform.travel_agents.view_logs');
    }
}
