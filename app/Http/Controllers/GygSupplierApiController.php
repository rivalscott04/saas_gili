<?php

namespace App\Http\Controllers;

use App\Services\GygSupplierApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class GygSupplierApiController extends Controller
{
    public function __construct(private readonly GygSupplierApiService $service)
    {
    }

    public function getAvailabilities(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'productId' => ['required', 'string', 'max:255'],
            'fromDateTime' => ['required', 'string'],
            'toDateTime' => ['required', 'string'],
        ]);

        return response()->json($this->service->getAvailabilities(
            $validated['productId'],
            $validated['fromDateTime'],
            $validated['toDateTime']
        ));
    }

    public function reserve(Request $request): JsonResponse
    {
        $data = $request->input('data', []);
        $this->validate($request, [
            'data.productId' => ['required', 'string', 'max:255'],
            'data.dateTime' => ['required', 'string'],
            'data.gygBookingReference' => ['required', 'string', 'max:255'],
            'data.bookingItems' => ['required', 'array', 'min:1'],
            'data.bookingItems.*.category' => ['required', 'string'],
            'data.bookingItems.*.count' => ['required', 'integer', 'min:1'],
            'data.bookingItems.*.groupSize' => ['nullable', 'integer', 'min:1'],
        ]);

        if (! is_array($data)) {
            return response()->json([
                'errorCode' => 'VALIDATION_FAILURE',
                'errorMessage' => 'Invalid payload',
            ]);
        }

        return response()->json($this->service->reserve($data));
    }

    public function cancelReservation(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data.reservationReference' => ['required', 'string', 'max:255'],
            'data.gygBookingReference' => ['required', 'string', 'max:255'],
        ]);

        return response()->json(
            $this->service->cancelReservation((string) $validated['data']['reservationReference'])
        );
    }

    public function book(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data.productId' => ['required', 'string', 'max:255'],
            'data.reservationReference' => ['required', 'string', 'max:255'],
            'data.gygBookingReference' => ['required', 'string', 'max:255'],
            'data.currency' => ['required', 'string', 'size:3'],
            'data.dateTime' => ['required', 'string'],
            'data.bookingItems' => ['required', 'array', 'min:1'],
            'data.bookingItems.*.category' => ['required', 'string'],
            'data.bookingItems.*.count' => ['required', 'integer', 'min:1'],
            'data.bookingItems.*.groupSize' => ['nullable', 'integer', 'min:1'],
            'data.travelers' => ['required', 'array', 'min:1'],
            'data.comment' => ['required', 'string'],
        ]);

        /** @var array<string, mixed> $payload */
        $payload = $validated['data'];

        return response()->json($this->service->book($payload));
    }

    public function cancelBooking(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'data.bookingReference' => ['required', 'string', 'max:255'],
            'data.gygBookingReference' => ['required', 'string', 'max:255'],
            'data.productId' => ['required', 'string', 'max:255'],
        ]);

        return response()->json($this->service->cancelBooking((string) $validated['data']['bookingReference']));
    }

    public function notify(Request $request): JsonResponse
    {
        $request->validate([
            'data.notificationType' => ['required', 'string'],
            'data.description' => ['required', 'string'],
            'data.supplierName' => ['required', 'string'],
            'data.integrationName' => ['required', 'string'],
            'data.productDetails' => ['required', 'array'],
            'data.notificationDetails' => ['required', 'array'],
            'data.dateTime' => ['required', 'string'],
        ]);

        return response()->json($this->service->notify());
    }

    public function pricingCategories(string $productId): JsonResponse
    {
        return response()->json($this->service->pricingCategories($productId));
    }

    public function supplierProducts(string $supplierId): JsonResponse
    {
        return response()->json($this->service->supplierProducts($supplierId));
    }

    public function addons(string $productId): JsonResponse
    {
        return response()->json($this->service->addons($productId));
    }

    public function productDetails(string $productId): JsonResponse
    {
        return response()->json($this->service->productDetails($productId));
    }
}
