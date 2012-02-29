<?php
namespace tests {

    use Lazy\Schema\SchemaDeclare;
    use Lazy\Schema;

    class NameSchema extends SchemaDeclare
    {
        function schema()
        {

            $this->column('id')
                ->type('integer')
                ->primary()
                ->autoIncrement();

            $this->column('name')
                ->isa('str')
                ->required()
                ->varchar(128);

            $this->column('address')
                ->isa('str')
                ->validator( function($val,$args,$record) { 
                    if( preg_match( '/fuck/', $val ) )
                        return array( false , "Please don't" );
                    return array( true , "Message" );
                })
                ->defaultBuilder( function() { 
                    return 'Default Address';
                })
                ->varchar(256);

            $this->column('confirmed')
                ->isa('bool')
                ->boolean();
        }
    }

}
