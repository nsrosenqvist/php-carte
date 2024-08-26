<?php

declare(strict_types=1);

namespace Carte\Exceptions;

use RuntimeException;
use Throwable;

class SerializationException extends RuntimeException
{
    public function __construct(string $message = 'Failed to serialize object', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
