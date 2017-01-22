<?php

namespace Maghead\TableParser;

class TypeInfo
{
    public $type;

    public $length;

    public $precision;

    public $isa;

    public $fullQualifiedTypeName;

    public $unsigned;

    public $enum = array();

    public $set = array();

    public function __construct($typeName = null, $length = null)
    {
        $this->type = $typeName;
        $this->length = $length;
    }

    public function getType()
    {
        return $this->type;
    }

    public function getLength()
    {
        return $this->length;
    }

    public function getPrecision()
    {
        return $this->precision;
    }
}
