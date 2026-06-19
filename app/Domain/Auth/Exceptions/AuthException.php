<?php

declare(strict_types=1);

namespace App\Domain\Auth\Exceptions;

use Exception;

//20 Auth Exceptions
//30 Auth Exceptions
//50 Auth Exceptions

//This abstract class is used to define the base exception for all authentication related exceptions. It extends the built-in Exception class and adds a status code property to the exception. The status code is set to 400 by default, but can be overridden in the constructor.
abstract class AuthException extends Exception
{
    public function __construct(
        string $message,
        int $statusCode = 400
    ) {
        parent::__construct($message, $statusCode);
    }

    public function statusCode(): int
    {
        return $this->getCode();
    }
}