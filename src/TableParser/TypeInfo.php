<?php

namespace Maghead\TableParser;

/**
 * Plain old object for column type info
 */
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
}
