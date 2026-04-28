<?php

namespace App\Http\Controllers;

use App\Services\GetYourGuideSupplierApiPlaygroundService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;
use InvalidArgumentException;

class GetYourGuideSupplierApiPlaygroundController extends Controller
{
    public function __construct(private readonly GetYourGuideSupplierApiPlaygroundService $playground)
    {
        $this->middleware('auth');
        $this->middleware('ensure.user.access');
    }

    public function index(Request $request): View|\Illuminate\Http\RedirectResponse
    {
        if (! $this->isPlaygroundEnabled()) {
            abort(404);
        }

        $viewer = $request->user();
        if (! $this->canUsePlayground($viewer)) {
            return redirect()->route('root');
        }

        return view('apps-gyg-supplier-api-playground');
    }

    public function invoke(Request $request): JsonResponse
    {
        if (! $this->isPlaygroundEnabled()) {
            abort(404);
        }

        $viewer = $request->user();
        if (! $this->canUsePlayground($viewer)) {
            abort(403);
        }

        $validated = $request->validate([
            'operation' => ['required', 'string', Rule::in(GetYourGuideSupplierApiPlaygroundService::operationIds())],
            'base_url' => ['required', 'string', 'max:512'],
            'auth_user' => ['required', 'string', 'max:255'],
            'auth_password' => ['required', 'string', 'max:255'],
            'path_params' => ['nullable', 'array'],
            'path_params.*' => ['nullable', 'string', 'max:512'],
            'query' => ['nullable', 'array'],
            'query.*' => ['nullable', 'string', 'max:2048'],
            'body' => ['nullable', 'string', 'max:131072'],
        ]);

        $allowLocalHttp = app()->isLocal();

        try {
            $result = $this->playground->invoke(
                $validated['operation'],
                $validated['base_url'],
                $validated['auth_user'],
                $validated['auth_password'],
                $validated['path_params'] ?? [],
                $validated['query'] ?? [],
                $validated['body'] ?? null,
                $allowLocalHttp,
            );
        } catch (InvalidArgumentException $e) {
            return response()->json([
                'ok' => false,
                'error' => $e->getMessage(),
            ], 422);
        }

        if (! ($result['ok'] ?? false)) {
            // Upstream returned 4xx/5xx — still JSON for the UI; avoid 502 so the client shows the real status.
            if (array_key_exists('status', $result) && is_int($result['status'])) {
                return response()->json($result);
            }

            return response()->json($result, 502);
        }

        return response()->json($result);
    }

    private function isPlaygroundEnabled(): bool
    {
        return (bool) config('gyg_supplier_playground.enabled', false) || app()->isLocal();
    }

    private function canUsePlayground($viewer): bool
    {
        return $viewer && $viewer->isSuperAdmin();
    }
}
