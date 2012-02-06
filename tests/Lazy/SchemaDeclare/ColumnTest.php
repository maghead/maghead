<?php


class ColumnTest extends PHPUnit_Framework_TestCase
{


    function createColumn($name)
    {
        return new \Lazy\Schema\SchemaDeclare\Column( $name );
    }

    function testVarchar()
    {
        ok( $c = $this->createColumn('title') );
        $c->isa('string')
            ->varchar(128)
            ->default( 'string' )
            ->validator('is_string');

        ok( $this->createColumn('size')->integer() );

        ok( $c->export() );

        // var_dump( var_export( $c->attributes, true ) ); 
    }

}

