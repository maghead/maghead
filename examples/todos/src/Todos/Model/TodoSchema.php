<?php

namespace Todos\Model;

use Maghead\Schema\DeclareSchema;

class TodoSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('title')
            ->varchar(128)
            ->required()
            ;

        $this->column('done')
            ->boolean()
            ->default(false);

        $this->column('description')
            ->text();

        $this->column('created_at')
            ->timestamp()
            ->default(function() {
                return date('c');
            });

        $this->seeds('Todos\Seed');
    }
}
