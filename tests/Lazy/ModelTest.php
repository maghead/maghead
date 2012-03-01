<?php
require_once 'tests/schema/tests/AuthorBooks.php';
use Lazy\SchemaSqlBuilder;

class ModelTest extends PHPUnit_Framework_ModelTestCase
{

    public function getModels()
    {
        return array( 
            '\tests\AuthorSchema', 
            '\tests\BookSchema',
            '\tests\AuthorBookSchema',
            '\tests\NameSchema',
        );
    }


    public function testClass()
    {
        foreach( $this->getModels() as $class ) 
            class_ok( $class );
    }


    /****************************
     * Basic CRUD Test 
     ***************************/
	public function testModel()
	{
        $author = new \tests\Author;
        ok( $author->_schema );

        $a2 = new \tests\Author;
        $ret = $a2->find( array( 'name' => 'A record does not exist.' ) );
        ok( ! $ret->success );
        ok( ! $a2->id );

        $a2->loadOrCreate( array( 'name' => 'Record1' , 'email' => 'record@record.org' , 'identity' => 'record' ) , 'name' );
        ok( $id = $a2->id );

        $a2->loadOrCreate( array( 'name' => 'Record1' , 'email' => 'record@record.org' , 'identity' => 'record' ) , 'name' );
        is( $id, $a2->id );

        $ret = $a2->create(array( 'name' => 'long string \'` long string' , 'email' => 'email' , 'identity' => 'id' ));
        ok( $ret->success );
        ok( $a2->id );

        $ret = $a2->create(array( 'xxx' => true, 'name' => 'long string \'` long string' , 'email' => 'email2' , 'identity' => 'id2' ));
        ok( $ret->success );
        ok( $a2->id );



        $ret = $author->create(array());
        ok( $ret );
        ok( ! $ret->success );
        ok( $ret->message );
        is( 'Empty arguments' , $ret->message );

        $query = $author->createQuery();
        ok( $query );

        $ret = $author->create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        ok( $ret );
        ok( $id = $ret->id );
        ok( $ret->success );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );

        $ret = $author->load( $id );
        ok( $ret->success );
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->find(array( 'name' => 'Foo' ));
        ok( $ret->success );
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->update(array( 'name' => 'Bar' ));
        ok( $ret->success );

        is( 'Bar', $author->name );

        $ret = $author->delete();
        ok( $ret->success );

        $data = $author->toArray();
        ok( empty($data), 'should be empty');
    }


	public function testFilter()
	{
        $name = new \tests\Name;
        $ret = $name->create(array(  'name' => 'Foo' , 'country' => 'Taiwan' , 'address' => 'John' ));
		ok( $ret );
		ok( $ret->success );
		is( 'XXXX' , $name->address , 'Be canonicalized' );
	}

	public function testValueTypeConstraint()
	{
		// if it's a str type , we should not accept types not str.
		$n = new \tests\Name;
		$ret = $n->create(array( 'name' => false , 'country' => 'Tokyo' ));
		ok( ! $ret->success );

		$ret = $n->create(array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 123 ));
		ok( ! $ret->success );
	}

    public function testDefaultBuilder()
    {
        $name = new \tests\Name;
        $ret = $name->create(array(  'name' => 'Foo' , 'country' => 'Taiwan' ));

        ok( $ret->success );
        ok( $ret->validations );

        ok( $ret->validations['address'] );
        ok( $ret->validations['address']->success );

        ok( $vlds = $ret->getSuccessValidations() );
        count_ok( 1, $vlds );

        ok( $name->id );
        ok( $name->address );

        $ret = $name->create(array(  'name' => 'Foo', 'address' => 'fuck' , 'country' => 'Tokyo' ));
        ok( $ret->validations );

        foreach( $ret->getErrorValidations() as $vld ) {
            is( false , $vld->success );
            is( 'Please don\'t',  $vld->message );
        }
    }

    public function testStaticFunctions() 
    {
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        ok( $record->_result->success );

        $record = \tests\Author::load( (int) $record->_result->id );
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
                ->back()
                ->execute();
        ok( $ret->success );

        $ret = \tests\Author::delete()
            ->where()
                ->equal('name','Rename')
            ->back()->execute();
        ok( $ret->success );

	}
}

