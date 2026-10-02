<?php

namespace App\Exceptions;

use Exception;

class GroupNotFoundException extends Exception
{
    public function __construct(
        string $message = 'Group not found.'
    ) {
        parent::__construct($message);
    }
}