<?php

namespace Maghead\Exception;

use LogicException;

class TableNameConversionException extends LogicException
{
    public $className;

    public function __construct($message, $className)
    {
        $this->className = $className;
        parent::__construct($message);
    }
}
