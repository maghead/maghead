<?php
namespace LazyRecord\Exception;
use Exception;
use LazyRecord\Schema\SchemaInterface;

class SchemaRelatedException extends Exception
{
    public $schema;

    public function __construct($message, SchemaInterface $schema) {
        $this->schema = $schema;
        parent::__construct($message);
    }

    public function getSchema() {
        return $this->schema;
    }

    public function getSchemaClass() {
        return get_class($this->schema);
    }
}




