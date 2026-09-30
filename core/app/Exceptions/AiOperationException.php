<?php

namespace App\Exceptions;

use RuntimeException;
use Throwable;

class AiOperationException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        public readonly int $httpStatus,
        public readonly ?string $requestId = null,
        ?Throwable $previous = null,
    ) {
        parent::__construct(__('backend.aiErrorGeneric'), 0, $previous);
    }
}
