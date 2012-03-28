<?php
use LazyRecord\Schema\SqlBuilder;

class PgsqlModelTest extends PHPUnit_Framework_ModelTestCase
{
    public $dsn = 'pgsql:dbname=lazy_test';

    public $schemaPath = 'tests/schema';

    public function getModels()
    {
        return array( 
            '\tests\AuthorSchema', 
            '\tests\BookSchema',
            '\tests\AuthorBookSchema',
            '\tests\NameSchema',
            '\tests\AddressSchema',
            '\tests\UserSchema',
        );
    }

	function testCRUD()
	{
        $author = new \tests\Author;
        ok( $author->_schema );

        $ret = $author->create(array());

        is( 'Empty arguments' , $ret->message );
        is( false , $ret->success );

        $book = new \tests\Book;
        $ret = $book->create(array( 
            'title' => 'title',
            'subtitle' => 'subtitle',
        ));
        
        ok( $ret->success );
        ok( $book->id );
        ok( $book->delete()->success );


        $ret = $book->create(array( 
            'title' => 'ti--string--tle--\'q"qq',
            'subtitle' => 'subtitle',
        ));
        ok( $book->id );
        ok( $ret->success );
        ok( $ret = $book->delete() );
        ok( $ret->success );




        $query = $author->createQuery();
        ok( $query );

        $ret = $author->create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        ok( $ret );

        // sqlite does not support last_insert_id: ok( $ret->id ); 
        ok( $ret->success );
        ok( $ret->id );
        is( 1 , $ret->id );
        ok( $author->created_on );
        ok( $author->email );

        $ret = $author->load(1);
        ok( $ret->success );

        is( 1 , $author->id );

        
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->update(array( 'name' => 'Bar' ));
        ok( $ret->success );
        

        is( 'Bar', $author->name );

        $ret = $author->delete();
        ok( $ret->success );

        /**
         * Static CRUD Test 
         */
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        ok( $id = $record->id );
        ok( $record->_result->id );
        ok( $record->_result->success );

        $record = \tests\Author::load( (int) $record->_result->id );
        ok( $record );
        ok( $record->id );

        $record = \tests\Author::load( array( 
            'id' => $id
        ));

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

        $this->resultOK( true, $ret );


        $ret = \tests\Author::delete()
            ->where()
                ->equal('name','Rename')
            ->back()->execute();
        ok( $ret->success );
	}


}

