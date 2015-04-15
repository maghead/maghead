<?php
namespace LazyRecord\TableParser;
use PDO;
use Exception;

class TypeInfo { 

    public $type;

    public $length;

    public $precision;

    public $isa;

    public $fullQualifiedTypeName;

    public $unsigned;

    public function __construct($typeName, $length = NULL)
    {
        $this->type = $typeName;
        $this->length = $length;
    }

    public function getType() {
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

