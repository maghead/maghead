<?php

namespace LazyRecord;

class RetValue
{
    public $ok;
    public $msg;
    public $type;
    public $field;

    function __construct( $field , $ok , $msg = null , $value = null ) { 
        $this->field = $field;
        $this->ok = $ok;

        $this->msg = $msg;
        $this->value = $value;
    }

    function type($type) { $this->type = $type; return $this; }
    function value($value) { $this->value = $value; return $this; }

    function getType()  { return $this->type; }
    function get_value() { return $this->value; }
    function get_msg() { return $this->msg; }
    function success() { return $this->ok; }

    function __toString() { return $this->msg; }
}

class ValidateResult extends RetValue {  };

?>
