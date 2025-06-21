<?php

declare(strict_types=1);

return [
    /*
    |--------------------------------------------------------------------------
    | Module Generation Options
    |--------------------------------------------------------------------------
    |
    | This file contains configuration for the syncing-module package.
    |
    */

    // Base namespace for generated classes
    'base_namespace' => 'App',

    // NetSuite related options
    'netsuite' => [
        'namespace' => 'Netsuite',
    ],

    // Path options for generated files
    'paths' => [
        'controllers' => 'Http/Controllers',
        'services' => 'Services/Netsuite',
        'actions' => 'Actions/Netsuite',
        'dtos' => 'Dtos/Netsuite',
        'requests' => 'Http/Requests/Netsuite',
    ],
];
