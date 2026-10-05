<?php

return [
    'issuer' => env('AUTH_ISSUER', 'eduvia-auth'),
    'audience' => env('AUTH_AUDIENCE', 'eduvia'),
    'kid' => 'auth-1',
    'access_ttl' => (int) env('ACCESS_TTL_MIN', 15) * 60,
    'refresh_ttl_days' => (int) env('REFRESH_TTL_DAYS', 14),
    'private_key' => env('AUTH_PRIVATE_KEY_PATH', storage_path('keys/private.pem')),
    'public_key' => env('AUTH_PUBLIC_KEY_PATH', storage_path('keys/public.pem')),
    'service_token' => env('SERVICE_TOKEN_SUPER_ADMIN'),

    // RBAC: papel -> permissoes incluidas nos claims do JWT ('*' = todas).
    'roles' => [
        'platform_admin' => ['*'],
        'platform_ops' => ['dashboard.view', 'tenants.view', 'tenants.create', 'tenants.suspend', 'plans.view'],
        'platform_support' => ['dashboard.view', 'tenants.view', 'plans.view'],
        'school_admin' => ['school.manage', 'school.view', 'finance.view', 'finance.manage', 'grades.manage', 'attendance.manage'],
        'school_teacher' => ['school.view', 'grades.manage', 'attendance.manage'],
        'school_finance' => ['school.view', 'finance.view', 'finance.manage'],
        'school_guardian' => ['guardian.view'],
    ],
];
