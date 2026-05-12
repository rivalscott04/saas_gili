<?php

return [
    /*
    |--------------------------------------------------------------------------
    | GetYourGuide Supplier API (public /1/* endpoints)
    |--------------------------------------------------------------------------
    |
    | Credentials used by GetYourGuide self-testing tool and runtime calls.
    | Keep these values secret in real environments.
    |
    */
    'username' => (string) env('GYG_SUPPLIER_API_USERNAME', ''),
    'password' => (string) env('GYG_SUPPLIER_API_PASSWORD', ''),
    /**
     * Single integration user for GetYourGuide (or other connectivity partners).
     * When set together with platform_password, Basic Auth accepts this pair for any tenant;
     * the effective supplier is taken from the URL (e.g. /1/suppliers/{supplierId}/...)
     * or derived from productId when the official API does not send supplierId.
     */
    'platform_username' => (string) env('GYG_SUPPLIER_API_PLATFORM_USERNAME', ''),
    'platform_password' => (string) env('GYG_SUPPLIER_API_PLATFORM_PASSWORD', ''),
    /**
     * Optional multi-credential mode.
     *
     * Example JSON:
     * [
     *   {"username":"gyg_tenant_a","password":"secretA","supplier_id":"tenant-a"},
     *   {"username":"gyg_tenant_b","password":"secretB","supplier_id":"tenant-b"}
     * ]
     */
    'credentials' => array_values(array_filter((array) json_decode((string) env('GYG_SUPPLIER_API_CREDENTIALS', '[]'), true), static function ($row): bool {
        if (! is_array($row)) {
            return false;
        }

        $username = trim((string) ($row['username'] ?? ''));
        $password = trim((string) ($row['password'] ?? ''));
        $supplierId = trim((string) ($row['supplier_id'] ?? ''));

        return $username !== '' && $password !== '' && $supplierId !== '';
    })),

    /*
    |--------------------------------------------------------------------------
    | Demo/static response tuning
    |--------------------------------------------------------------------------
    |
    | This lightweight implementation provides deterministic responses for
    | self-testing flows. Product IDs can be allow-listed to prevent accidental
    | success for unknown products.
    |
    */
    'supplier_id' => (string) env('GYG_SUPPLIER_API_SUPPLIER_ID', 'Abc123'),
    'supplier_name' => (string) env('GYG_SUPPLIER_API_SUPPLIER_NAME', 'Desma Supplier'),
    'default_currency' => (string) env('GYG_SUPPLIER_API_DEFAULT_CURRENCY', 'EUR'),
    'pricing_mode' => (string) env('GYG_SUPPLIER_API_PRICING_MODE', 'individual'),
    'include_prices' => (bool) env('GYG_SUPPLIER_API_INCLUDE_PRICES', true),
    'max_participants_default' => (int) env('GYG_SUPPLIER_API_MAX_PARTICIPANTS_DEFAULT', 999),
    'availability_type' => (string) env('GYG_SUPPLIER_API_AVAILABILITY_TYPE', 'by_category'),
    'supported_ticket_categories' => array_values(array_filter(array_map(
        static fn (string $category): string => strtoupper(trim($category)),
        explode(',', (string) env('GYG_SUPPLIER_API_SUPPORTED_TICKET_CATEGORIES', 'ADULT,CHILD'))
    ))),
    'valid_product_ids' => array_values(array_filter(array_map(
        static fn (string $id): string => trim($id),
        explode(',', (string) env('GYG_SUPPLIER_API_VALID_PRODUCT_IDS', 'prod123,prod124,prod125,PPYM1U'))
    ))),
];
