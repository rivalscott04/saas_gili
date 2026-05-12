<?php

namespace App\Http\Controllers;

use App\Services\GygSupplierApiService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\ValidationException;

class GygSupplierApiController extends Controller
{
    public function __construct(private readonly GygSupplierApiService $service)
    {
    }

    public function getAvailabilities(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'productId' => ['required', 'string', 'max:255'],
                'fromDateTime' => ['required', 'string'],
                'toDateTime' => ['required', 'string'],
            ]);
        } catch (ValidationException $e) {
            return $this->gygError('VALIDATION_FAILURE', $this->flatValidationMessage($e));
        }

        try {
            return response()->json($this->service->getAvailabilities(
                $this->resolveSupplierId($request),
                $validated['productId'],
                $validated['fromDateTime'],
                $validated['toDateTime']
            ));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function reserve(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'data.productId' => ['required', 'string', 'max:255'],
                'data.dateTime' => ['required', 'string'],
                'data.gygBookingReference' => ['required', 'string', 'max:255'],
                'data.bookingItems' => ['required', 'array', 'min:1'],
                'data.bookingItems.*.category' => ['required', 'string'],
                'data.bookingItems.*.count' => ['required', 'integer', 'min:1'],
                'data.bookingItems.*.groupSize' => ['nullable', 'integer', 'min:1'],
            ]);
        } catch (ValidationException $e) {
            return $this->gygError('VALIDATION_FAILURE', $this->flatValidationMessage($e));
        }

        $data = $request->input('data', []);
        if (! is_array($data)) {
            return $this->gygError('VALIDATION_FAILURE', 'Invalid payload');
        }

        $data['supplierId'] = $this->resolveSupplierId($request);

        try {
            return response()->json($this->service->reserve($data));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function cancelReservation(Request $request): JsonResponse
    {
        try {
            $validated = $request->validate([
                'data.reservationReference' => ['required', 'string', 'max:255'],
                'data.gygBookingReference' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return $this->gygError('VALIDATION_FAILURE', $this->flatValidationMessage($e));
        }

        try {
            return response()->json(
                $this->service->cancelReservation((string) $validated['data']['reservationReference'])
            );
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function book(Request $request): JsonResponse
    {
        try {
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
                'data.travelers.0.firstName' => ['required', 'string', 'max:255'],
                'data.travelers.0.lastName' => ['required', 'string', 'max:255'],
                'data.travelers.0.email' => ['required', 'email', 'max:255'],
                'data.travelers.0.phoneNumber' => ['required', 'string', 'max:50'],
                'data.travelerHotel' => ['nullable', 'string', 'max:500'],
                'data.comment' => ['nullable', 'string'],
            ]);
        } catch (ValidationException $e) {
            return $this->gygError('VALIDATION_FAILURE', $this->flatValidationMessage($e));
        }

        /** @var array<string, mixed> $payload */
        $payload = $validated['data'];
        $payload['supplierId'] = $this->resolveSupplierId($request);

        try {
            return response()->json($this->service->book($payload));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function cancelBooking(Request $request): JsonResponse
    {
        try {
            $request->validate([
                'data.bookingReference' => ['required', 'string', 'max:255'],
                'data.gygBookingReference' => ['required', 'string', 'max:255'],
                'data.productId' => ['required', 'string', 'max:255'],
            ]);
        } catch (ValidationException $e) {
            return $this->gygError('VALIDATION_FAILURE', $this->flatValidationMessage($e));
        }

        try {
            return response()->json($this->service->cancelBooking((string) $request->input('data.bookingReference')));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function notify(Request $request): JsonResponse
    {
        try {
            return response()->json($this->service->notify());
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function pricingCategories(Request $request, string $productId): JsonResponse
    {
        try {
            return response()->json($this->service->pricingCategories($this->resolveSupplierId($request), $productId));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function supplierProducts(Request $request, string $supplierId): JsonResponse
    {
        if (! $request->attributes->get('gyg_supplier_platform_auth', false)) {
            $authSupplier = trim((string) $request->attributes->get('gyg_supplier_id', ''));
            if ($authSupplier !== '' && strcasecmp($authSupplier, $supplierId) !== 0) {
                return $this->gygError('AUTHORIZATION_FAILURE', 'Supplier ID does not match credentials');
            }
        }

        try {
            return response()->json($this->service->supplierProducts($supplierId));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function addons(Request $request, string $productId): JsonResponse
    {
        try {
            return response()->json($this->service->addons($this->resolveSupplierId($request), $productId));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    public function productDetails(Request $request, string $productId): JsonResponse
    {
        try {
            return response()->json($this->service->productDetails($this->resolveSupplierId($request), $productId));
        } catch (\Throwable $e) {
            return $this->gygError('INTERNAL_ERROR', $e->getMessage());
        }
    }

    private function resolveSupplierId(Request $request): string
    {
        return (string) $request->attributes->get('gyg_supplier_id', '');
    }

    private function gygError(string $errorCode, string $errorMessage): JsonResponse
    {
        return response()->json([
            'errorCode' => $errorCode,
            'errorMessage' => $errorMessage,
        ], 200);
    }

    private function flatValidationMessage(ValidationException $e): string
    {
        $messages = collect($e->errors())->flatten()->all();

        return implode('; ', $messages) ?: 'Validation failed';
    }
}
