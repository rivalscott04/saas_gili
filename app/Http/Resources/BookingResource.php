<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class BookingResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $customer = $this->relationLoaded('customer') ? $this->customer : null;
        $viewer = $request->user();
        $canViewRevenue = $viewer !== null && $viewer->isAdmin();

        $data = [
            'id' => $this->id,
            'tour_id' => $this->tour_id,
            'tour_name' => $this->tour_name,
            'customer_name' => $customer?->full_name ?? $this->customer_name,
            'customer_email' => $customer?->email ?? $this->customer_email,
            'customer_phone' => $customer?->phone ?? $this->customer_phone,
            'tour_start_at' => $this->tour_start_at?->toISOString(),
            'date' => $this->tour_start_at?->toDateString(),
            'time' => $this->tour_start_at?->format('H:i'),
            'location' => $this->location,
            'guide_name' => $this->guide_name,
            'status' => $this->status,
            'booking_source' => $this->booking_source,
            'confirm_url' => $this->confirmation_token
                ? $this->frontendMagicLinkUrl()
                : null,
            'customer_response' => $this->customer_response,
            'customer_responded_at' => $this->customer_responded_at?->toISOString(),
            'participants' => $this->participants,
            'notes' => $this->notes,
            'internal_notes' => $this->internal_notes,
            'assigned_to_name' => $this->assigned_to_name,
            'tags' => $this->tags ?? [],
            'needs_attention' => $this->needs_attention,
        ];

        if ($canViewRevenue) {
            $data['channel'] = $this->channel;
            $data['channel_order_id'] = $this->channel_order_id;
            $data['supplier_booking_reference'] = $this->supplier_booking_reference;
            $data['currency'] = $this->currency;
            $data['gross_amount'] = $this->gross_amount !== null ? (float) $this->gross_amount : null;
            $data['commission_amount'] = $this->commission_amount !== null ? (float) $this->commission_amount : null;
            $data['net_amount'] = $this->net_amount !== null ? (float) $this->net_amount : null;
            $data['fx_rate_to_idr'] = $this->fx_rate_to_idr !== null ? (float) $this->fx_rate_to_idr : null;
            $data['revenue_amount'] = $this->revenue_amount !== null ? (float) $this->revenue_amount : null;
            $data['pricing_payload_json'] = $this->pricing_payload_json;
        }

        return $data;
    }

    private function frontendMagicLinkUrl(): string
    {
        $base = config('app.frontend_url');

        return $base.'/booking/'.$this->id.'/respond?'.http_build_query([
            'token' => $this->confirmation_token,
        ]);
    }
}
