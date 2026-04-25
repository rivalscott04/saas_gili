<?php

namespace App\Providers;

use App\Models\Booking;
use App\Models\ChannelSyncLog;
use App\Models\ChatTemplate;
use App\Models\TravelAgent;
use App\Policies\BookingPolicy;
use App\Policies\ChannelSyncLogPolicy;
use App\Policies\ChatTemplatePolicy;
use App\Policies\TravelAgentPolicy;
use Illuminate\Foundation\Support\Providers\AuthServiceProvider as ServiceProvider;

class AuthServiceProvider extends ServiceProvider
{
    /**
     * The policy mappings for the application.
     *
     * @var array<class-string, class-string>
     */
    protected $policies = [
        Booking::class => BookingPolicy::class,
        ChannelSyncLog::class => ChannelSyncLogPolicy::class,
        ChatTemplate::class => ChatTemplatePolicy::class,
        TravelAgent::class => TravelAgentPolicy::class,
    ];

    /**
     * Register any authentication / authorization services.
     *
     * @return void
     */
    public function boot()
    {
        $this->registerPolicies();

        //
    }
}
