<?php
namespace TestApp\Model;
use Maghead\Schema;

class PostSchema extends Schema
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

