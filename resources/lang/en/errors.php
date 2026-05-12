<?php

declare(strict_types=1);

return [
    'success' => 'success',
    'kernel' => [
        'request' => [
            'bad_request' => 'Bad request.',
            'validation_failed' => 'Validation failed.',
        ],
        'auth' => [
            'unauthorized' => 'Please sign in first.',
            'token_expired' => 'Token has expired.',
        ],
        'permission' => [
            'forbidden' => 'You do not have permission to access this API.',
        ],
        'resource' => [
            'not_found' => 'Resource not found.',
        ],
        'server' => [
            'internal_error' => 'Internal server error.',
        ],
    ],
];
