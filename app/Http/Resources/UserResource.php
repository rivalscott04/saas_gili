<?php

namespace App\Http\Resources;

use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

class UserResource extends JsonResource
{
    /**
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        $this->resource->loadMissing('tenant.activeCategories:id,code');
        $tenantCategoryCodes = $this->tenant?->activeCategories
            ->pluck('code')
            ->values()
            ->all() ?? [];

        return [
            'id' => $this->id,
            'name' => $this->name,
            'email' => $this->email,
            'role' => $this->role,
            'avatar' => $this->avatar,
            'tenant_categories' => $tenantCategoryCodes,
            'created_at' => $this->created_at?->toISOString(),
        ];
    }
}
