<?php

namespace App\Exceptions;

use Exception;

class UnauthorizedGroupAccessException extends Exception
{
    public function __construct(
        string $message = 'You are not authorized to access this group.'
    ) {
        parent::__construct($message);
    }
}