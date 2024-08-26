<?php

declare(strict_types=1);

namespace Carte\Exceptions;

use Throwable;
use TypeError;

class UnsupportedMethodException extends TypeError
{
    public function __construct(string $message = 'Request method not supported', int $code = 400, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
