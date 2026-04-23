<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class CustomerResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'id' => $this->id,
            'full_name' => $this->full_name,
            'email' => $this->email,
            'phone' => $this->phone,
            'country_code' => $this->country_code,
            'external_source' => $this->external_source,
            'external_customer_ref' => $this->external_customer_ref,
            'bookings_count' => $this->bookings_count ?? 0,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
