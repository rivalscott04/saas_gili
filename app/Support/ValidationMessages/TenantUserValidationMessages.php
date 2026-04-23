<?php

namespace App\Support\ValidationMessages;

class TenantUserValidationMessages
{
    /**
     * @return array<string, string>
     */
    public static function storeUser(): array
    {
        return [
            'name.required' => 'Nama lengkap wajib diisi.',
            'name.regex' => 'Nama lengkap tidak boleh kosong.',
            'email.required' => 'Email wajib diisi.',
            'email.email' => 'Format email tidak valid.',
            'email.unique' => 'Email sudah digunakan.',
            'password.required' => 'Password wajib diisi.',
            'password.min' => 'Password minimal 8 karakter.',
            'role.required' => 'Role wajib dipilih.',
            'role.in' => 'Role tidak valid untuk tenant ini.',
            'tenant_code.required' => 'Tenant wajib dipilih.',
            'tenant_code.exists' => 'Tenant yang dipilih tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function storeRole(): array
    {
        return [
            'role_name.required' => 'Nama role wajib diisi.',
            'role_name.regex' => 'Nama role tidak boleh kosong.',
            'tenant_code.required' => 'Tenant wajib dipilih.',
            'tenant_code.exists' => 'Tenant yang dipilih tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function updateUserStatus(): array
    {
        return [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'tenant_code.required' => 'Tenant wajib dipilih.',
            'tenant_code.exists' => 'Tenant yang dipilih tidak valid.',
        ];
    }
}
