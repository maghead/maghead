<?php
namespace LazyRecord\OperationResult;

class OperationResult
{
    public $id;

    public $success;

    public $message;

    public $sql;

    public $validations;

    public $errors;

    public $args;


    /**
     * variables from SQL Query Builder
     */
    public $vars;

    public function __construct($message = null, $extra = array() )
    {
        $this->message = $message;
        foreach( $extra as $k => $v ) {
            $this->$k = $v;
        }
    }


    /**
     * returns an array contains success validations 
     */
    public function getSuccessValidations() 
    {
        $vlds = array();
        foreach( $this->validations as $k => $vld ) {
            if( true === $vld->success )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }

    public function getErrorValidations() 
    {
        $vlds = array();
        foreach( $this->validations as $k => $vld ) {
            if( false === $vld->success )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }

    public function __toString() {
        $msg = $this->message . "\n";
        if( $this->exception ) {
            $msg .= ' Exception:' . $this->exception->getMessage() . "\n";
            if( $this->sql ) {
                $msg .= ' SQL:' . $this->sql . "\n";
            }
        }
        return $msg;
    }
}



