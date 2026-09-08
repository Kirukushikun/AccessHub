<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Access vocabulary
    |--------------------------------------------------------------------------
    | Fixed, short lists. Projects do not invent their own role names here.
    */

    'roles' => [
        'requestor' => 'Requestor',
        'division_head' => 'Division head',
        'vp' => 'Vice president',
        'user' => 'User',
    ],

    'scopes' => [
        'all' => 'All projects',
        'selected' => 'Selected projects',
    ],

    'acceptance' => [
        'open' => 'Open',
        'explicit' => 'Explicit',
    ],

    'environments' => ['local', 'staging', 'production'],

    /*
    |--------------------------------------------------------------------------
    | Org reference data — display + filtering only, never affects access
    |--------------------------------------------------------------------------
    */

    'farms' => [
        'BFC',
        'BFC-IRAQ',
        'BROOKDALE',
        'FEEDMILL',
        'HATCHERY',
        'PFC',
        'RH/BBGC',
    ],

    'departments' => [
        'Accounting',
        'Audit',
        'Feedmill',
        'General Services',
        'Human Resources',
        'IT and Security Services',
        'Poultry',
        'Purchasing',
        'Sales & Marketing',
        'Swine',
        'Treasury',
    ],

    /*
    |--------------------------------------------------------------------------
    | Tunables
    |--------------------------------------------------------------------------
    */

    // A connection with no successful sync in this many days is shown as "stale".
    'stale_after_days' => 14,

    // Connection enrollment codes expire this many minutes after being generated.
    'code_ttl_minutes' => 15,

    // Login lockout.
    'login_max_attempts' => 3,
    'login_lockout_minutes' => 15,

];
