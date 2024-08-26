<?php

declare(strict_types=1);

namespace Carte\Exceptions;

use Throwable;
use ValueError;

class InvalidRedirectException extends ValueError
{
    public function __construct(string $message = 'Invalid redirect', int $code = 500, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
