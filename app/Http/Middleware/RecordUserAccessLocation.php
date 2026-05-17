<?php

namespace App\Http\Middleware;

use App\Services\UserAccessLogService;
use Closure;
use Illuminate\Http\Request;
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
        if ($user !== null && $request->isMethodSafe()) {
            $this->accessLogService->recordFromRequest($user, $request);
        }

        return $response;
    }
}
