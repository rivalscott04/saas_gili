<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Controllers\Controller;
use App\Http\Resources\CustomerResource;
use App\Models\Booking;
use App\Services\CustomerService;
use Illuminate\Http\Request;

class CustomerController extends Controller
{
    public function __construct(private readonly CustomerService $customerService)
    {
    }

    public function index(Request $request)
    {
        $this->authorize('viewAny', Booking::class);

        $customers = $this->customerService->paginate($request->all(), $request->user());

        return CustomerResource::collection($customers);
    }
}
