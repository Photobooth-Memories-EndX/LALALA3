<?php

namespace App\Services\EmployeeSync;

use Exception;

class EmployeeApiException extends Exception
{
    protected ?int $statusCode;

    public function __construct(string $message = "", int $statusCode = 0, ?\Throwable $previous = null)
    {
        parent::__construct($message, $statusCode, $previous);
        $this->statusCode = $statusCode > 0 ? $statusCode : null;
    }

    public function getStatusCode(): ?int
    {
        return $this->statusCode;
    }
}
