<?php

namespace App\Http\Middleware;

use App\Jobs\RecordUserAccessLogJob;
use App\Services\UserAccessLogService;
use Closure;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Symfony\Component\HttpFoundation\Response;

class RecordUserAccessLocation
{
    public function __construct(private readonly UserAccessLogService $accessLogService)
    {
    }

    public function handle(Request $request, Closure $next): Response
    {
        $response = $next($request);

        $user = $request->user();
        if ($user === null || ! $request->isMethodSafe()) {
            return $response;
        }

        $ip = (string) $request->ip();
        if ($ip === '' || $this->accessLogService->wasRecentlyRecorded((int) $user->id, $ip)) {
            return $response;
        }

        RecordUserAccessLogJob::dispatchAfterResponse(
            (int) $user->id,
            $ip,
            Str::limit((string) $request->userAgent(), 500, ''),
        );

        return $response;
    }
}
