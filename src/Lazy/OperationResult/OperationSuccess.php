<?php
namespace Lazy\OperationResult;

class OperationSuccess extends OperationResult
{
    public $success = true;

    public function __toString()
    {
        return (string) $this->message;
    }
}


