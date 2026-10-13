<?php

declare(strict_types=1);

namespace Library\Adapters\Inbound\Http;

use RuntimeException;

final class ApiException extends RuntimeException
{
    public function __construct(
        public readonly string $errorCode,
        string $message,
        public readonly int $status,
        public readonly array $details = []
    ) {
        parent::__construct($message);
    }
}
