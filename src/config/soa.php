<?php

return [
    'domain' => env('SOA_DOMAIN', 'platformserviceaccount.com'), // TODO: Remove default value. Too informative.
    'environment' => env('SOA_ENVIRONMENT', 'staging'),          // TODO: Remove default value. Too informative.
    'gateway_uri' => env('SOA_GATEWAY_URI', 'api-gateway'),      // TODO: Remove default value. Too informative.
    'aggregator_prefix' => env('SOA_AGGREGATOR_PREFIX', 'agg'),  // TODO: Remove default value. Too informative.

    // TODO: Add documentation on services definition.
    'services' => [

        // TODO: Add documentation on cloud vs local.
        'cloud' => [
            // TODO: Remove default entries.
            'orders' => env('SOA_CLOUD_ORDERS_URI', 'orders'),
            'email-receipt' => env('SOA_CLOUD_EMAIL_RECEIPT_URI', 'email-receipt'),
        ],
        'local' => [
            // TODO: Remove default entries.
            'orders' => env('SOA_LOCAL_ORDERS_URI', 'ordersepos')
        ],
    ],

    // TODO: Add documentation on auth definition.
    'auth' => [
        // TODO: Remove default entries.
        'orders' => [
            'email' => env('SOA_CLOUD_ORDERS_AUTH_EMAIL'),
            'password' => env('SOA_CLOUD_ORDERS_AUTH_PASSWORD'),
        ],
    ],
];