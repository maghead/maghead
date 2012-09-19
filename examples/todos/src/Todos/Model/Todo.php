<?php
namespace Todos\Model;
use LazyRecord\BaseModel;

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
	const schema_proxy_class = 'Todos\\Model\\TodoSchemaProxy';
	const collection_class = 'Todos\\Model\\TodoCollection';
	const model_class = 'Todos\\Model\\Todo';
	const table = 'todos';
#boundary end e6c43a79f921219423c18a77ab2386d9
}

