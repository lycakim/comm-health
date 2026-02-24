<?php

namespace App\Exceptions;

use App\Models\Patient;
use Exception;

class DuplicatePatientException extends Exception
{
    public function __construct(
        string $message,
        public readonly ?Patient $existingPatient = null
    ) {
        parent::__construct($message);
    }
}
