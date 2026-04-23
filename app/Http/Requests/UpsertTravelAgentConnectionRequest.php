<?php

namespace App\Http\Requests;

use App\Support\ValidationMessages\TravelAgentValidationMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpsertTravelAgentConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        return $this->user()?->isAdmin() === true;
    }

    public function rules(): array
    {
        $rules = [
            'api_key' => ['required', 'string', 'max:2000'],
            'api_secret' => ['nullable', 'string', 'max:2000'],
            'account_reference' => ['required', 'string', 'max:191'],
        ];

        if ($this->user()?->isSuperAdmin()) {
            $rules['tenant_code'] = ['required', 'string', 'max:120', Rule::exists('tenants', 'code')];
        }

        return $rules;
    }

    public function messages(): array
    {
        return TravelAgentValidationMessages::connection();
    }

    public function attributes(): array
    {
        return [
            'api_key' => 'API Key',
            'api_secret' => 'API Secret',
            'account_reference' => 'Account Ref',
            'tenant_code' => 'Tenant',
        ];
    }
}
