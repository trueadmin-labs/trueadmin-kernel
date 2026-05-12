<?php

declare(strict_types=1);

return [
    'success' => 'success',
    'kernel' => [
        'request' => [
            'bad_request' => '请求错误',
            'validation_failed' => '参数校验失败',
        ],
        'auth' => [
            'unauthorized' => '请先登录',
            'token_expired' => 'Token 已过期',
        ],
        'permission' => [
            'forbidden' => '无权访问该接口',
        ],
        'resource' => [
            'not_found' => '资源不存在',
        ],
        'server' => [
            'internal_error' => '服务内部错误',
        ],
    ],
];
