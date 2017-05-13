<?php

namespace Maghead\Runtime;

use ValidationKit\ValidationMessage;
use Exception;

class ResultException extends Exception
{
}

class Result
{
    const TYPE_NONE = 0;
    const TYPE_CREATE = 1;
    const TYPE_LOAD = 2;
    const TYPE_UPDATE = 3;
    const TYPE_DELETE = 4;


    public $key;

    /**
     * @var bool Success or fail.
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

    /**
     * column key => ValidationMessage object.
     */
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

    /**
     * @var const CREATE_RESULT, READ_RESULT, UPDATE_RESULT, DELETE_RESULT
     */
    public $type = self::TYPE_NONE;

    public $code;

    public $exception;

    public $shard;

    public $subResults;

    public function __construct($success, $message)
    {
        $this->success = $success;
        $this->error = !$success;
        $this->message = $message;
    }

    /**
     * Create successful result
     */
    public static function success($msg = null, array $extra = array())
    {
        $result = new self(true, $msg);
        foreach ($extra as $k => $v) {
            $result->$k = $v;
        }
        return $result;
    }

    /**
     * Create failed result.
     */
    public static function failure($msg = null, array $extra = array())
    {
        $result = new self(false, $msg);
        foreach ($extra as $k => $v) {
            $result->$k = $v;
        }
        return $result;
    }

    public function setType($type)
    {
        $this->type = $type;
    }

    public function getType($type)
    {
        return $this->type;
    }

    public function getKey()
    {
        return $this->key;
    }

    public function __get($name)
    {
        if ($name == "id") {
            return $this->key;
        }
    }


    public function getColumnValidation($columnName)
    {
        if (isset($this->validations[$columnName])) {
            return $this->validations[$columnName];
        }
    }


    /**
     * returns an array contains success validations.
     */
    public function getSuccessValidations()
    {
        $vlds = array();
        foreach ($this->validations as $k => $vld) {
            if ($vld['valid']) {
                $vlds[$k] = $vld;
            }
        }

        return $vlds;
    }

    /**
     * Returns an array of ValidationMessage objects.
     */
    public function getErrorValidations()
    {
        $vlds = array();
        foreach ($this->validations as $k => $vld) {
            if (!$vld['valid']) {
                $vlds[$k] = $vld;
            }
        }

        return $vlds;
    }

    public function throwExceptionIfFailed()
    {
        if ($this->error) {
            if ($this->exception) {
                throw $this->exception;
            }
            throw new ResultException($this->message);
        }
    }

    public function __toString()
    {
        $msg = $this->message."\n";
        if ($this->exception) {
            $msg .= ' Exception:'.$this->exception->getMessage()."\n";
            if ($this->sql) {
                $msg .= ' SQL:'.$this->sql."\n";
            }
        }

        if ($this->validations) {
            foreach ($this->validations as $k => $vld) {
                $msg .= $k.': '.($vld->valid ? 'Valid' : 'Invalid')."\n";
            }
        }

        return $msg;
    }

    public function silentError($desc = null, $messageType = 0)
    {
        error_log(($desc ? "$desc:" : '').$this->message, $messageType);
    }
}
