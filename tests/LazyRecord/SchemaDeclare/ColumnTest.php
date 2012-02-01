<?php


class ColumnTest extends PHPUnit_Framework_TestCase
{


    function createColumn($name)
    {
        return new \LazyRecord\SchemaDeclare\Column( $name );
    }

    function testVarchar()
    {
        ok( $c = $this->createColumn('title') );
        $c->isa('string')
            ->varchar(128)
            ->default( 'string' )
            ->validator('is_string');
        
    }

}

