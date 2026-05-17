<?php

namespace App\Jobs;

use App\Models\User;
use App\Services\UserAccessLogService;
use Illuminate\Bus\Queueable;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Foundation\Bus\Dispatchable;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Queue\SerializesModels;

class RecordUserAccessLogJob implements ShouldQueue
{
    use Dispatchable, InteractsWithQueue, Queueable, SerializesModels;

    public function __construct(
        public readonly int $userId,
        public readonly string $ipAddress,
        public readonly ?string $userAgent,
    ) {
    }

    public function handle(UserAccessLogService $accessLogService): void
    {
        $user = User::query()->find($this->userId);
        if ($user === null) {
            return;
        }

        $accessLogService->recordAccess($user, $this->ipAddress, $this->userAgent);
    }
}
