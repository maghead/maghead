<?php
namespace LazyRecord\OperationResult;
use Exception;

class OperationResult
{
    public $id;


    /**
     * @var boolean Success or fail.
     */
    public $success;

    public $error;


    /**
     * @var string Message
     */
    public $message;


    /**
     * @var string SQL query string
     */
    public $sql;

    public $validations;

    public $errors;


    /**
     * @var array Arguments before applying to SQL builder.
     */
    public $args;


    /**
     * @var array Variables that built from SQL Query Builder
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
            if( $vld->valid )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }

    public function getErrorValidations() 
    {
        $vlds = array();
        foreach( $this->validations as $k => $vld ) {
            if( $vld->valid )
                $vlds[$k] = $vld;
        }
        return $vlds;
    }

    public function throwExceptionIfFailed()
    {
        if ( ! $this->success ) {
            if ( $this->exception ) {
                throw $this->exception;
            }
            throw new Exception($this->message);
        }
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



