<?php
namespace Maghead\Model;
use Maghead\BaseCollection;
class MetadataCollectionBase
    extends BaseCollection
{
    const SCHEMA_PROXY_CLASS = 'Maghead\\Model\\MetadataSchemaProxy';
    const MODEL_CLASS = 'Maghead\\Model\\Metadata';
    const TABLE = '__meta__';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
    public static function getSchema()
    {
        static $schema;
        if ($schema) {
           return $schema;
        }
        return $schema = new \Maghead\Model\MetadataSchemaProxy;
    }
}
