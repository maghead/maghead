<?php
/**
 * Testing models:
 *   1. Author
 *   2. Book
 *   3. Address
 */
class BasicCRUDTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 
            'tests\\AuthorSchema',
            'tests\\BookSchema',
            'tests\\AuthorBookSchema',
            'tests\\AddressSchema',
        );
    }

    public function testTitleIsRequired()
    {
        $b = new \tests\Book;
        $ret = $b->find( array( 'name' => 'LoadOrCreateTest' ) );
        result_fail( $ret );
        ok( ! $b->id );
    }


    public function testRecordRawCreateBook()
    {
        $b = new \tests\Book;
        ok($b);
        $b->rawCreate(array( 'title' => 'Go Programming' ));
        ok($b->id);
        result_ok( $b->delete() );
    }

    public function testRecordRawUpdateBook()
    {
        $b = new \tests\Book;
        ok($b);
        $b->rawCreate(array( 'title' => 'Go Programming' ));
        ok($b->id);
        $b->rawUpdate(array( 'title' => 'Perl Programming' ));
        ok($b->id);
        result_ok( $b->delete() );
    }


    public function testLoadOrCreateModel() 
    {
        $b = new \tests\Book;

        $ret = $b->create(array( 'title' => 'Should Not Load This' ));
        result_ok( $ret );

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        result_ok( $ret );

        $id = $b->id;
        ok($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id, $b->id, 'is the same ID');


        $b2 = new \tests\Book;
        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        result_ok($ret);
        is($id,$b2->id);

        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest2'  ) , 'title' );
        result_ok($ret);
        ok($b2);
        ok($id != $b2->id , 'we should create anther one'); 

        $b3 = new \tests\Book;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        result_ok($ret);
        ok($b3);
        ok($id != $b3->id , 'we should create anther one'); 

        $b3->delete();

        foreach( $b2->flushResults() as $r ) {
            result_ok( \tests\Book::delete($r->id)->execute() );
        }
        foreach( $b->flushResults() as $r ) {
            result_ok( \tests\Book::delete($r->id)->execute() );
        }
    }

    public function booleanTrueTestDataProvider()
    {
        return array(
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 1 ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '1' ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => true ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'true' ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => ' ' ) ),  // space string (true)
        );
    }

    public function booleanFalseTestDataProvider()
    {
        return array(
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 0 ) ),
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '0' ) ),
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => false ) ),
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'false' ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '' ) ),  // empty string should be (false)
            // array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'aa' ) ),
            // array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'bb' ) ),
        );
    }

    public function testModelUpdateRawValue() 
    {
        $author = new \tests\Author;
        $ret = $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        result_ok($ret);
        $ret = $author->update(array( 'id' => array('id + 3') ));
        result_ok($ret);
    }

    public function testManyToManyRelationRecordCreate()
    {
        $author = new \tests\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( 
            $book = $author->books->create( array( 
                'title' => 'Programming Perl I',
                ':author_books' => array( 'created_on' => '2010-01-01' ),
            ))
        );
        ok( $book->id );
        is( 'Programming Perl I' , $book->title );

        is( 1, $author->books->size() );
        is( 1, $author->author_books->size() );
        ok( $author->author_books[0] );
        ok( $author->author_books[0]->created_on );
        is( '2010-01-01', $author->author_books[0]->created_on->format('Y-m-d') );

        $author->books[] = array( 
            'title' => 'Programming Perl II',
        );
        is( 2, $author->books->size() , '2 books' );

        $books = $author->books;
        is( 2, $books->size() , '2 books' );

        foreach( $books as $book ) {
            ok( $book->id );
            ok( $book->title );
        }

        foreach( $author->books as $book ) {
            ok( $book->id );
            ok( $book->title );
        }

        $books = $author->books;
        is( 2, $books->size() , '2 books' );
        $author->delete();
    }


    public function testPrimaryKeyIdIsInteger()
    {
        $author = new \tests\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        // XXX: in different database engine, it's different.
        // sometimes it's string, sometimes it's integer
        // ok( is_string( $author->getValue('id') ) );
        ok( is_integer( $author->get('id') ) );
        $author->delete();
    }


    public function testManyToManyRelationFetchRecord()
    {
        $author = new \tests\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        $book = $author->books->create(array( 'title' => 'Book Test' ));
        ok( $book );
        ok( $book->id , 'book is created' );

        $ret = $book->delete();
        ok( $ret->success );

        $ab = new \tests\AuthorBook;
        $book = new \tests\Book;

        // should not include this
        ok( $book->create(array( 'title' => 'Book I Ex' ))->success );

        ok( $book->create(array( 'title' => 'Book I' ))->success );
        result_ok( $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        )) );

        ok( $book->create(array( 'title' => 'Book II' ))->success );
        $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));

        ok( $book->create(array( 'title' => 'Book III' ))->success );
        $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));

        // retrieve books from relationshipt
        $author->flushCache();
        $books = $author->books;
        is( 3, $books->size() , 'We have 3 books' );


        $bookTitles = array();
        foreach( $books->items() as $item ) {
            $bookTitles[ $item->title ] = true;
            $item->delete();
        }

        count_ok( 3, array_keys($bookTitles) );
        ok( $bookTitles[ 'Book I' ] );
        ok( $bookTitles[ 'Book II' ] );
        ok( $bookTitles[ 'Book III' ] );
        ok( ! isset($bookTitles[ 'Book I Ex' ] ) );

        $author->delete();
    }

    public function testHasManyRelationCreate2()
    {
        $author = new \tests\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        // append items
        $author->addresses[] = array( 'address' => 'Harvard' );
        $author->addresses[] = array( 'address' => 'Harvard II' );

        is(2, $author->addresses->size() , 'just two item' );

        $addresses = $author->addresses->items();
        ok( $addresses );
        is( 'Harvard' , $addresses[0]->address );

        $a = $addresses[0];
        ok( $retAuthor = $a->author );
        ok( $retAuthor->id );
        ok( $retAuthor->name );
        is( 'Z', $retAuthor->name );

        $author->delete();
    }

    public function testHasManyRelationCreate()
    {
        $author = new \tests\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        $address = $author->addresses->create(array( 
            'address' => 'farfaraway'
        ));

        ok( $address->id );
        ok( $address->author_id );
        is( $author->id, $address->author_id );

        is( 'farfaraway' , $address->address );

        $address->delete();
        $author->delete();

    }

    public function testHasManyRelationFetch()
    {
        $author = new \tests\Author;
        ok( $author );

        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        $address = new \tests\Address;
        ok( $address );

        $address->create(array( 
            'author_id' => $author->id,
            'address' => 'Taiwan Taipei',
        ));
        ok( $address->author );
        ok( $address->author->id );
        is( $author->id, $address->author->id );

        $address->create(array( 
            'author_id' => $author->id,
            'address' => 'Taiwan Taipei II',
        ));

        // xxx: provide getAddresses() method generator
        $addresses = $author->addresses;
        ok( $addresses );

        $items = $addresses->items();
        ok( $items );

        ok( $addresses[0] );
        ok( $addresses[1] );
        ok( ! isset($addresses[2]) );
        ok( ! @$addresses[2] );

        ok( $addresses[0]->id );
        ok( $addresses[1]->id );

        ok( $size = $addresses->size() );
        is( 2 , $size );

        foreach( $author->addresses as $ad ) {
            ok( $ad->delete()->success );
        }

        $author->delete();
    }

    public function testRecordUpdateWithRawSQL()
    {
        $n = new \tests\Book;
        $n->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        is( 0 , $n->view );
        $ret = $n->update(array( 
            'view' => array('view + 1')
        ));

        ok( $ret->success );
        is( 1 , $n->view );

        $n->update(array( 
            'view' => array('view + 3')
        ));
        $ret = $n->reload();
        ok( $ret->success );
        is( 4, $n->view );

        result_ok($n->delete());
    }



    public function testZeroInflator()
    {
        $b = new \tests\Book;
        $ret = $b->create(array( 'title' => 'Create X' , 'view' => 0 ));
        result_ok($ret);
        ok( $b->id );
        is( 0 , $b->view );

        $ret = $b->load($ret->id);
        result_ok($ret);
        ok( $b->id );
        is( 0 , $b->view );
        $b->delete();
    }


    public function testUpdateWithReloadOption()
    {
        $b = new \tests\Book;
        $ret = $b->create(array( 'title' => 'Create Y' , 'view' => 0 ));

        // test incremental
        $ret = $b->update(array( 'view'  => array('view + 1') ), array('reload' => true));
        result_ok($ret);
        is( 1,  $b->view );

        $ret = $b->update(array( 'view'  => array('view + 1') ), array('reload' => true));
        result_ok($ret);
        is( 2,  $b->view );
        $ret = $b->delete();
        result_ok($ret);
    }

    public function testStaticCreateMethod()
    {
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        ok( $record->popResult()->success );
    }

    public function testStaticLoadMethod()
    {
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        $record2 = \tests\Author::load($record->id );
        ok($record2->id);

        $record3 = \tests\Author::load((int) $record->id);
        ok($record3->id);

        $record4 = \tests\Author::load( array( 'id' => $record->id ));
        ok( $record4 );
        ok( $record4->id );
    }

    public function testStaticFunctions()
    {
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        $record = \tests\Author::load( (int) $record->popResult()->id );

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


