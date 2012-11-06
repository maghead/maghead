<?php
namespace LazyRecord\Model;
use LazyRecord\BaseModel;

class Metadata extends BaseModel 
{
    public function schema($schema) 
    {
        $schema->table('__meta__');
        $schema->column('id')
            ->integer()
            ->primary()
            ->autoIncrement()
            ;
        $schema->column('name')
            ->varchar(128);
        $schema->column('value')
            ->varchar(256);
    }
#boundary start 9ad333f20bc76786c74e9d1d15be87ce
	const schema_proxy_class = 'LazyRecord\\Model\\MetadataSchemaProxy';
	const collection_class = 'LazyRecord\\Model\\MetadataCollection';
	const model_class = 'LazyRecord\\Model\\Metadata';
	const table = '__meta__';
#boundary end 9ad333f20bc76786c74e9d1d15be87ce
}

