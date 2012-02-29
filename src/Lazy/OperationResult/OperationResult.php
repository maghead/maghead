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
            if( $vld->success === true )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }

    public function getErrorValidations() 
    {
        $vlds = array();
        foreach( $this->validations as $k => $vld ) {
            if( $vld->success === false )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }



}



