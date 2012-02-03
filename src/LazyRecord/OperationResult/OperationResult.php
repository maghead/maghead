<?php
namespace LazyRecord\OperationResult;

class OperationResult
{
    public $id;
    public $code;
    public $success;
    public $message;

    public function __construct($message = null)
    {
        $this->message = $message;
    }
}



