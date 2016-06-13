<?php
namespace LazyRecord\Model;
use LazyRecord\BaseCollection;
class MetadataCollectionBase
    extends BaseCollection
{
    const SCHEMA_PROXY_CLASS = 'LazyRecord\\Model\\MetadataSchemaProxy';
    const MODEL_CLASS = 'LazyRecord\\Model\\Metadata';
    const TABLE = '__meta__';
    const READ_SOURCE_ID = 'default';
    const WRITE_SOURCE_ID = 'default';
    const PRIMARY_KEY = 'id';
}
