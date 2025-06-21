<?php

declare(strict_types=1);

return [
    'consumer_key' => env('NS_CONSUMER_KEY', ''),
    'consumer_secret' => env('NS_CONSUMER_SECRET', ''),
    'token_id' => env('NS_TOKEN_ID', ''),
    'token_secret' => env('NS_TOKEN_SECRET', ''),
    'script_id' => env('NS_SCRIPT_ID', ''),
    'deploy_id' => env('NS_DEPLOY_ID', ''),
    'account' => env('NS_ACCOUNT', ''),
    'oauth_sig_method' => env('NS_OAUTH_SIGN_METHOD', ''),
    'oauth_version' => env('NS_OAUTH_VERSION', ''),
    'url' => env('NS_URL', ''),
    'netsuite-token' => env('NS_WEB_TOKEN', ''),
    'endpoints' => [
        'post' => [
            'sync' => [
                'test' => 'createTest',
            ],
        ],
    ],
];
