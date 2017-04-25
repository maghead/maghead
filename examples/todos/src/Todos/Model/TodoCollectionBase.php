<?php
namespace Todos\Model;

use Maghead\Runtime\BaseCollection;

class TodoCollectionBase
    extends BaseCollection
{

    const SCHEMA_PROXY_CLASS = 'Todos\\Model\\TodoSchemaProxy';

    const MODEL_CLASS = 'Todos\\Model\\Todo';

    const TABLE = 'todos';

    const READ_SOURCE_ID = 'master';

    const WRITE_SOURCE_ID = 'master';

    const PRIMARY_KEY = 'id';

    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Todos\Model\TodoSchemaProxy;
    }
}
