<?php

namespace App\Http\Requests;

use App\Models\TravelAgent;
use App\Support\ValidationMessages\TravelAgentValidationMessages;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Http\Exceptions\HttpResponseException;
use Illuminate\Validation\Rule;

class UpsertTravelAgentConnectionRequest extends FormRequest
{
    public function authorize(): bool
    {
        $user = $this->user();
        $travelAgent = $this->route('travelAgent');
        if (! $user || ! $travelAgent instanceof TravelAgent) {
            return false;
        }

        return match ($this->route()?->getName()) {
            'travel-agents.test' => $user->can('testConnection', $travelAgent),
            'travel-agents.connect' => $user->can('manageConnection', $travelAgent),
            default => false,
        };
    }

    protected function failedAuthorization(): void
    {
        throw new HttpResponseException(redirect()->route('root'));
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
