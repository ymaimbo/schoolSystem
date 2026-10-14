<?php
// config/billing_access.php

return [
    /*
    |----------------------------------------------------------------------
    | Access Rules by Billing Stage
    |----------------------------------------------------------------------
    | locked: <25%
    | quarter_only: >=25% and <50%
    | half_only: >=50% and <100%
    | full: >=100%
    |
    | Requirement:
    | principal + deputy_principal must be blocked at quarter_only
    | and only allowed from half_only stage.
    */

    'allowed_roles_by_stage' => [
        'locked' => [
            'accountant',
        ],
        'quarter_only' => [
            'accountant',
            'secretary',
            'dean',
            'hod',
            'store_keeper',
            'class_teacher',
            'subject_teacher',
        ],
        'half_only' => [
            'principal',
            'deputy_principal',
            'accountant',
            'secretary',
            'dean',
            'hod',
            'store_keeper',
            'class_teacher',
            'subject_teacher',
        ],
        'full' => [
            'principal',
            'deputy_principal',
            'accountant',
            'secretary',
            'dean',
            'hod',
            'store_keeper',
            'class_teacher',
            'subject_teacher',
        ],
    ],

    /*
    |----------------------------------------------------------------------
    | Routes always allowed even when restricted
    |----------------------------------------------------------------------
    */
    'always_allowed_route_names' => [
        'profile.edit',
        'profile.update',
        'profile.destroy',
        'logout',

        // Keep finance payment routes reachable for allowed billing-settlement flow
        'admin.billing.index',
        'admin.billing.show',
        'admin.billing.payments.store',
    ],
];