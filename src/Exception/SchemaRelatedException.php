<?php

namespace Maghead\Exception;

use Exception;
use Maghead\Schema\Schema;

class SchemaRelatedException extends Exception
{
    public $schema;

    public function __construct(Schema $schema, $message)
    {
        $this->schema = $schema;

        $cls = get_class($schema);
        parent::__construct("{$cls}: {$message}");
    }

    public function getSchema()
    {
        return $this->schema;
    }

    public function getSchemaClass()
    {
        return get_class($this->schema);
    }
}
