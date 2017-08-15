<?php

return [

    /*
    |--------------------------------------------------------------------------
    | Microservice Aggregator Transport
    |--------------------------------------------------------------------------
    |
    | Configuration options for microservice aggregation transport. These
    | options are used to define the environment the services operate in
    | along with possible services that can be communicated with.
    |
    */

    // The top level domain of the service environment.
    'domain' => env('SOA_DOMAIN'),

    // The CI branch. For example master.
    'branch' => env('SOA_BRANCH'),

    // The CI environment. For example dev or staging.
    'environment' => env('SOA_ENVIRONMENT'),

    // The URI of the API gateway.
    'gateway_uri' => env('SOA_GATEWAY_URI'),

    // The prefix, if any, that is applied to aggregator URIs.
    'aggregator_prefix' => env('SOA_AGGREGATOR_PREFIX'),

    /*
    |--------------------------------------------------------------------------
    | Services
    |--------------------------------------------------------------------------
    |
    | A list of services that data can be aggregated from. A service can either
    | be treated as 'cloud' or 'local', and should be nested appropriately.
    |
    | The list should be defined with the machine name of the service as the
    | key and an array containing the URI as the value and optionally a version
    | as the value. These values can be pulled from an environment
    | variable in the format SOA_[CLOUD|LOCAL]_[SERVICE_NAME]_[URI|VERSION].
    |
    | Example: 'test' => [
    |              'uri' => env('SOA_CLOUD_TEST_URI'),
    |              'version' => env('SOA_CLOUD_TEST_VERSION')
    |          ]
    |
    */

    'services' => [

        'cloud' => [],

        'local' => [],

    ],

    /*
    |--------------------------------------------------------------------------
    | Authentication
    |--------------------------------------------------------------------------
    |
    | A list of authentication details for cloud based services. These details
    | will be used only for cloud based services i.e. where the service class
    | extends the CloudService abstract class.
    |
    | The list should be defined with the machine name of the service as the
    | key and an array containing email and password as the value.
    |
    | The value of the email and password can pulled from an environment
    | variables in the format SOA_CLOUD_[SERVICE_NAME]_AUTH_[EMAIL|PASSWORD].
    |
    | Example: 'test' => [
    |              'email' => env('SOA_CLOUD_TEST_AUTH_EMAIL'),
    |              'password' => env('SOA_CLOUD_TEST_AUTH_PASSWORD')
    |          ]
    |
    */

    'auth' => [],

];