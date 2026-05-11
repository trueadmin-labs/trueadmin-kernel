<?php

declare(strict_types=1);

namespace TrueAdmin\Kernel\Constant;

use Hyperf\Constants\Annotation\Constants;
use Hyperf\Constants\Annotation\Message;

#[Constants]
enum ErrorCode: string implements ErrorCodeInterface
{
    use ErrorCodeTrait;

    #[Message('errors.success')]
    case SUCCESS = 'SUCCESS';

    #[Message('errors.kernel.request.bad_request')]
    case BAD_REQUEST = 'KERNEL.REQUEST.BAD_REQUEST';

    #[Message('errors.kernel.request.validation_failed')]
    case VALIDATION_FAILED = 'KERNEL.REQUEST.VALIDATION_FAILED';

    #[Message('errors.kernel.auth.unauthorized')]
    case UNAUTHORIZED = 'KERNEL.AUTH.UNAUTHORIZED';

    #[Message('errors.kernel.auth.token_expired')]
    case TOKEN_EXPIRED = 'KERNEL.AUTH.TOKEN_EXPIRED';

    #[Message('errors.kernel.permission.forbidden')]
    case FORBIDDEN = 'KERNEL.PERMISSION.FORBIDDEN';

    #[Message('errors.kernel.resource.not_found')]
    case NOT_FOUND = 'KERNEL.RESOURCE.NOT_FOUND';

    #[Message('errors.kernel.server.internal_error')]
    case SERVER_ERROR = 'KERNEL.SERVER.INTERNAL_ERROR';

}
