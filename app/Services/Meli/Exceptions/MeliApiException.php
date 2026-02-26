<?php

namespace App\Services\Meli\Exceptions;

use RuntimeException;
use Throwable;

class MeliApiException extends RuntimeException
{
    public function __construct(
        string $message,
        public readonly ?int $statusCode = null,
        public readonly ?array $context = null,
        ?Throwable $previous = null
    ) {
        parent::__construct($message, 0, $previous);
    }
}