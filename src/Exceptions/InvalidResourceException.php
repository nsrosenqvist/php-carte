<?php

declare(strict_types=1);

namespace Carte\Exceptions;

use Throwable;
use TypeError;

class InvalidResourceException extends TypeError
{
    public function __construct(string $message = 'Invalid resource type', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
