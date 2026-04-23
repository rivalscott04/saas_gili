<?php

namespace App\Support\ValidationMessages;

class OperationsResourceValidationMessages
{
    /**
     * @return array<string, string>
     */
    public static function store(): array
    {
        return [
            'resource_type.required' => 'Tipe resource wajib dipilih.',
            'resource_type.in' => 'Tipe resource tidak valid.',
            'name.required' => 'Nama resource wajib diisi.',
            'name.regex' => 'Nama resource tidak boleh kosong.',
            'reference_code.required' => 'Kode referensi wajib diisi.',
            'capacity.required' => 'Kapasitas wajib diisi untuk tipe ini.',
            'capacity.integer' => 'Kapasitas harus berupa angka.',
            'capacity.min' => 'Kapasitas minimal 1.',
            'tenant_code.required' => 'Tenant wajib dipilih.',
            'tenant_code.exists' => 'Tenant yang dipilih tidak valid.',
        ];
    }

    /**
     * @return array<string, string>
     */
    public static function blockOut(): array
    {
        return [
            'status.required' => 'Status wajib dipilih.',
            'status.in' => 'Status tidak valid.',
            'blocked_from.required' => 'Tanggal mulai block out wajib diisi.',
            'blocked_from.date' => 'Format tanggal mulai block out tidak valid.',
            'blocked_until.date' => 'Format tanggal selesai block out tidak valid.',
            'blocked_until.after_or_equal' => 'Tanggal selesai harus sama atau setelah tanggal mulai.',
            'block_reason.required' => 'Alasan block out wajib diisi.',
            'tenant_code.required' => 'Tenant wajib dipilih.',
            'tenant_code.exists' => 'Tenant yang dipilih tidak valid.',
        ];
    }
}
