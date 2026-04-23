<?php

namespace App\Support\ValidationMessages;

class TravelAgentValidationMessages
{
    /**
     * @return array<string, string>
     */
    public static function connection(): array
    {
        return [
            'api_key.required' => 'API Key wajib diisi.',
            'api_key.max' => 'API Key terlalu panjang.',
            'account_reference.required' => 'Account Ref wajib diisi.',
            'account_reference.max' => 'Account Ref terlalu panjang.',
            'tenant_code.required' => 'Tenant wajib dipilih.',
            'tenant_code.exists' => 'Tenant yang dipilih tidak valid.',
        ];
    }
}
