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

    public function __construct($typeName, $length = NULL)
    {
        $this->type = $typeName;
        $this->length = $length;
    }
}

