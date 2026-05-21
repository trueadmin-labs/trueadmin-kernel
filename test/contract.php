<?php

declare(strict_types=1);

use TrueAdmin\Kernel\Context\Actor;
use TrueAdmin\Kernel\Http\ApiResponse;

$autoload = dirname(__DIR__) . '/vendor/autoload.php';
if (! is_file($autoload)) {
    $autoload = dirname(__DIR__, 2) . '/TrueAdmin/hyperf/vendor/autoload.php';
}

require $autoload;

$success = ApiResponse::success(['ok' => true], ['requestId' => 'test']);
assert($success['success'] === true);
assert($success['data']['ok'] === true);
assert($success['meta']['requestId'] === 'test');

$failure = ApiResponse::fail('TEST.ERROR', 'failed', ['field' => 'name']);
assert($failure['success'] === false);
assert($failure['error']['code'] === 'TEST.ERROR');
assert($failure['error']['details']['field'] === 'name');

$actor = new Actor('admin', 1, 'trueadmin', claims: ['roleCodes' => ['super-admin']]);
assert($actor->toArray()['claims']['roleCodes'][0] === 'super-admin');
