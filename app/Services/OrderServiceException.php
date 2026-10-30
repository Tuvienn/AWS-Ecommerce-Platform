<?php

namespace App\Services;

use RuntimeException;

final class OrderServiceException extends RuntimeException
{
    public function __construct(
        private readonly string $userMessage,
        string $technicalMessage = ''
    ) {
        parent::__construct($technicalMessage !== '' ? $technicalMessage : $userMessage);
    }

    public function getUserMessage(): string
    {
        return $this->userMessage;
    }
}
