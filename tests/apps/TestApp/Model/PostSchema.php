<?php
namespace TestApp\Model;

use Maghead\Schema\DeclareSchema;

class PostSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('title')
            ->varchar(128);

        $this->column('content')
            ->text();

        $this->column('status')
            ->validValues([ 'publish', 'draft' ])
            ->varchar(20)
            ;

        $this->column('created_on')
            ->timestamp()
            ;

        $this->column('created_by')
            ->integer();
    }
}
