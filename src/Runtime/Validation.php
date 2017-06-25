<?php

namespace Maghead\Runtime;

class Validation
{
    public $valid;

    public $field;

    public $message;

    public function __construct($valid, $field, $message)
    {
        $this->valid = $valid;
        $this->field = $field;
        $this->message = $message;
    }
}
