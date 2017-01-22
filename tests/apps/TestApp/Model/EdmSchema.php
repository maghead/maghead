<?php
namespace TestApp\Model;
use Maghead\Schema;
use SQLBuilder\Raw;

class EdmSchema extends Schema
{
    public function schema()
    {
        $this->table('Edm');

        $this->column('edmNo')
            ->primary()
            ->integer()
            ->isa('int')
            ->autoIncrement();

        $this->column('edmTitle')
            ->varchar(256)
            ->isa('str');

        $this->column('edmStart')
            ->type('date')
            ->isa('DateTime');

        $this->column('edmEnd')
            ->type('date')
            ->isa('DateTime');

        $this->column('edmContent')
            ->text()
            ->isa('str');

        $this->column('edmUpdatedOn')
            ->timestamp()
            ->default(new Raw('CURRENT_TIMESTAMP'))
            ->onUpdate(new Raw('CURRENT_TIMESTAMP'))
            ;
    }
}

