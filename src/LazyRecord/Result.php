<?php
namespace LazyRecord;

class Result
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


    public $code;

    public $exception;

    public $debugInfo = array();

    public static function success($msg = null, $extra = array()) {
        $result = new self;
        $result->setSuccess();
        $result->setMessage($msg);
        foreach( $extra as $k => $v ) {
            $result->$k = $v;
        }
        return $result;
    }

    public static function failure($msg = null, $extra = array()) {
        $result = new self;
        $result->setError();
        $result->setMessage($msg);
        foreach( $extra as $k => $v ) {
            $result->$k = $v;
        }
        return $result;
    }



    public function setSuccess() {
        $this->success = true;
        $this->error = false;
    }

    public function setError() {
        $this->success = false;
        $this->error = true;
    }


    public function setDebugInfo(array $info) {
        $this->debugInfo = $info;
    }

    public function getDebugInfo() {
        return $this->debugInfo;
    }

    public function setMessage($msg) {
        $this->message = $msg;
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





