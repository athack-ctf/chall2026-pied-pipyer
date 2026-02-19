<?php

namespace App\Exceptions;

class NetworkException extends \Exception
{
    private array $redirectResponses;

    public function __construct(string $message, array $redirectResponses = [])
    {
        parent::__construct($message);
        $this->redirectResponses = $redirectResponses;
    }

    public function getRedirectResponses(): array
    {
        return $this->redirectResponses;
    }

    public function getHttpCode(): int
    {
        return 500;
    }
}
