<?php

declare(strict_types=1);

namespace Carte\Exceptions;

use Throwable;
use ValueError;

class RouteNotFoundException extends ValueError
{
    public function __construct(string $message = 'Route not found', int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
