<?php

declare(strict_types=1);

namespace Laratusk\Supervise\Exceptions;

use RuntimeException;

class ValidationException extends RuntimeException
{
    /**
     * @param  array<string, array<int, string>>  $errors
     */
    public function __construct(private readonly array $errors)
    {
        parent::__construct('Supervise configuration is invalid.');
    }

    /**
     * @return array<string, array<int, string>>
     */
    public function getErrors(): array
    {
        return $this->errors;
    }
}
