<?php

declare(strict_types=1);

namespace Carte\Exceptions;

use Throwable;
use ValueError;

class ManifestParseException extends ValueError
{
    public function __construct(string $message = 'File not found', int $code = 404, ?Throwable $previous = null)
    {
        parent::__construct($message, $code, $previous);
    }
}
