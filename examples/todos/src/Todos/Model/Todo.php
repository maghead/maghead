<?php
namespace Todos\Model;
use Maghead\BaseModel;

class Todo extends BaseModel 
{
    function schema($schema)
    {
        $schema->column('title')
            ->varchar(128)
            ->required()
            ;
        $schema->column('description')
            ->text();

        $schema->column('created_on')
            ->timestamp()
            ->default(function() {
                return date('c');
            });

        $schema->seeds('Todos\Seed');
    }

#boundary start e6c43a79f921219423c18a77ab2386d9
	const SCHEMA_PROXY_CLASS = 'Todos\\Model\\TodoSchemaProxy';
	const COLLECTION_CLASS = 'Todos\\Model\\TodoCollection';
	const MODEL_CLASS = 'Todos\\Model\\Todo';
	const TABLE = 'todos';
#boundary end e6c43a79f921219423c18a77ab2386d9
}

