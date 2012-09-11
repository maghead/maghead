<?php

class AuthorModelTest extends PHPUnit_Framework_ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array('tests\AuthorSchema');
    }

    public function testStaticFunctions() 
    {
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        ok( $record->popResult()->success );

        $record = \tests\Author::load( (int) $record->popResult()->id );
        ok( $record );
        ok( $id = $record->id );

        $record = \tests\Author::load( array( 'id' => $id ));
        ok( $record );
        ok( $record->id );

        /**
         * Which runs:
         *    UPDATE authors SET name = 'Rename' WHERE name = 'Mary'
         */
        $ret = \tests\Author::update(array( 'name' => 'Rename' ))
            ->where()
            ->equal('name','Mary')
            ->execute();
        ok( $ret->success );


        $ret = \tests\Author::delete()
            ->where()
            ->equal('name','Rename')
            ->execute();
        ok( $ret->success );
    }
}
