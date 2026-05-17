<?php

return [
    'tenant_picker_cache_seconds' => (int) env('PERFORMANCE_TENANT_PICKER_CACHE_SECONDS', 300),
    'onboarding_summary_cache_seconds' => (int) env('PERFORMANCE_ONBOARDING_SUMMARY_CACHE_SECONDS', 90),
    'dashboard_summary_cache_seconds' => (int) env('PERFORMANCE_DASHBOARD_SUMMARY_CACHE_SECONDS', 60),
    'landing_pricing_cache_seconds' => (int) env('PERFORMANCE_LANDING_PRICING_CACHE_SECONDS', 600),
    'register_categories_cache_seconds' => (int) env('PERFORMANCE_REGISTER_CATEGORIES_CACHE_SECONDS', 600),
    'audit_event_types_cache_seconds' => (int) env('PERFORMANCE_AUDIT_EVENT_TYPES_CACHE_SECONDS', 300),
    'onboarding_snapshot_debounce_seconds' => (int) env('PERFORMANCE_ONBOARDING_SNAPSHOT_DEBOUNCE_SECONDS', 300),
];
