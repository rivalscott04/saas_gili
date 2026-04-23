<?php

namespace App\Support\ValidationMessages;

class TenantInvoiceValidationMessages
{
    /**
     * @return array<string, string>
     */
    public static function branding(): array
    {
        return [
            'invoice_logo.required' => 'Logo invoice wajib diunggah.',
            'invoice_logo.image' => 'File logo harus berupa gambar.',
            'invoice_logo.mimes' => 'Format logo harus jpg, jpeg, png, atau webp.',
            'invoice_logo.max' => 'Ukuran logo maksimal 2MB.',
        ];
    }
}
