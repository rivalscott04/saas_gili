<?php

namespace App\Http\Requests;

use App\Models\Booking;
use App\Models\Tour;
use App\Models\User;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreManualBookingRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user() !== null && $this->user()->can('create', Booking::class);
    }

    protected function prepareForValidation(): void
    {
        if ($this->input('guide_name') === '') {
            $this->merge(['guide_name' => null]);
        }
    }

    public function withValidator(Validator $validator): void
    {
        $validator->after(function (Validator $validator): void {
            $tenantId = $this->resolveTenantIdForScope();
            if ($tenantId <= 0) {
                $validator->errors()->add('tour_id', 'Tenant tidak valid untuk memilih tour.');

                return;
            }

            $tourId = (int) $this->input('tour_id');
            $tourOk = Tour::query()
                ->where('id', $tourId)
                ->where('tenant_id', $tenantId)
                ->where('is_active', true)
                ->exists();
            if (! $tourOk) {
                $validator->errors()->add('tour_id', 'Pilih tour aktif dari tenant yang sama.');
            }

            $guideName = $this->input('guide_name');
            if ($guideName === null || $guideName === '') {
                return;
            }
            $viewer = $this->user();
            if ($viewer === null) {
                return;
            }
            $tenantId = $this->resolveTenantIdForScope();
            if ($tenantId <= 0) {
                $validator->errors()->add('guide_name', 'Tenant tidak valid untuk memilih guide.');

                return;
            }
            $ok = User::query()
                ->where('tenant_id', $tenantId)
                ->where('role', 'guide')
                ->where('name', $guideName)
                ->where(function ($q): void {
                    $q->whereNull('status')
                        ->orWhereRaw('LOWER(status) != ?', ['suspended']);
                })
                ->exists();
            if (! $ok) {
                $validator->errors()->add('guide_name', 'Pilih guide dari daftar yang tersedia.');
            }
        });
    }

    /**
     * @return array<string, mixed>
     */
    public function rules(): array
    {
        $viewer = $this->user();
        $rules = [
            'tour_id' => ['required', 'integer', Rule::exists('tours', 'id')],
            'tour_start_at' => ['required', 'date'],
            'participants' => ['required', 'integer', 'min:1', 'max:999'],
            'location' => ['nullable', 'string', 'max:500'],
            'guide_name' => ['nullable', 'string', 'max:255'],
            'status' => ['required', Rule::in(['standby', 'confirmed', 'pending', 'cancelled'])],
            'customer_name' => ['required', 'string', 'max:255'],
            'customer_email' => ['nullable', 'email', 'max:255'],
            'customer_phone' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string', 'max:5000'],
            'internal_notes' => ['nullable', 'string', 'max:5000'],
        ];

        if ($viewer !== null && $viewer->isSuperAdmin()) {
            // Field name must not be `tenant_id` (blocked on web by EnsureUserAccessPolicy).
            $rules['on_behalf_tenant_id'] = ['required', 'exists:tenants,id'];
        }

        if ($viewer !== null && $viewer->isAdmin()) {
            $rules['channel_order_id'] = ['nullable', 'string', 'max:255'];
            $rules['net_amount'] = ['nullable', 'numeric', 'min:0'];
        }

        return $rules;
    }

    private function resolveTenantIdForScope(): int
    {
        $viewer = $this->user();
        if (! $viewer) {
            return 0;
        }

        return $viewer->isSuperAdmin()
            ? (int) $this->input('on_behalf_tenant_id')
            : (int) $viewer->tenant_id;
    }
}
