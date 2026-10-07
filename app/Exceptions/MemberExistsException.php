<?php

namespace App\Exceptions;

use Exception;

class MemberExistsException extends Exception
{
    public function __construct(
        string $message = 'User is already a member of this group.'
    ) {
        parent::__construct($message);
    }
}
