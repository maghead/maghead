<?php
namespace Lazy\OperationResult;

class OperationError extends OperationResult
{
    public $success = false;
    public $exception;
    public $code;

    public function __toString()
    {
        $str = '';

        if( $this->code )
            $str .= '[' . $this->code . ']';

        $str .= ' ' . $this->message;

        if( $this->exception ) 
            $str .= ' E: ' . $this->exception->getMessage();
        return $str;
    }

}



