<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Services\TravelAgents\TravelAgentWebhookService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class TravelAgentWebhookController extends Controller
{
    public function __construct(private readonly TravelAgentWebhookService $service)
    {
    }

    public function getYourGuide(Request $request): JsonResponse
    {
        $tenantCode = strtolower((string) $request->header('X-Tenant-Code', ''));
        $signature = (string) $request->header('X-Webhook-Signature', '');
        $rawBody = $request->getContent();
        $payload = $request->json()->all();

        $result = $this->service->ingestGetYourGuideWebhook($tenantCode, $signature, $rawBody, is_array($payload) ? $payload : []);

        return response()->json([
            'message' => $result['message'],
        ], $result['status']);
    }
}
