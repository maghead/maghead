<?php
namespace LazyRecord\OperationResult;

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
            $str .= "\nException: " . $this->exception->__toString();
        if( $this->sql )
            $str .= "\nSQL: " . $this->sql;
        if( $this->vars )
            $str .= "\nVars: " . print_r($this->vars,true);
        if( $this->validations ) {
            $str .= "\nValidations: ";
            foreach( $this->validations as $v ) {
                $str .= "\n" . (
                    is_array($v) 
                        ? print_r($v,true) 
                        : (is_object($v) 
                            ? $v->__toString() 
                            : $v)
                );
            }
        }
        return $str;
    }

}

