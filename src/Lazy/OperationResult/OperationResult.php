<?php
namespace Lazy\OperationResult;

class OperationResult
{
    public $id;

    public $success;

    public $message;

    public $sql;

    public $validations;

    public $errors;

    public function __construct($message = null, $extra = array() )
    {
        $this->message = $message;
        foreach( $extra as $k => $v ) {
            $this->$k = $v;
        }
    }

    public function getSuccessValidations() 
    {
        $vlds = array();
        foreach( $this->validations as $k => $vld ) {
            if( $vld[0] === true )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }

    public function getErrorValidations() 
    {
        $vlds = array();
        foreach( $this->validations as $k => $vld ) {
            if( $vld[0] === false )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }



}



