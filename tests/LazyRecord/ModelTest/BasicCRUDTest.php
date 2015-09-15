<?php
use SQLBuilder\Raw;
use AuthorBooks\Model\Book ;
use LazyRecord\Testing\ModelTestCase;
use LazyRecord\RESULT;
/**
 * Testing models:
 *   1. Author
 *   2. Book
 *   3. Address
 */
class BasicCRUDTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 
            'AuthorBooks\Model\AuthorSchema',
            'AuthorBooks\Model\BookSchema',
            'AuthorBooks\Model\AuthorBookSchema',
            'AuthorBooks\Model\AddressSchema',
        );
    }

    public function setUp() {
        if( ! extension_loaded('pdo_' . $this->driver) ) {
            $this->markTestSkipped('pdo_' . $this->driver . ' extension is required for model testing');
            return;
        }
        parent::setUp();
    }

    /**
     * @rebuild false
     * @expectedException LazyRecord\QueryException
     */
    public function testTitleIsRequired()
    {
        $b = new \AuthorBooks\Model\Book ;
        $ret = $b->find(array( 'name' => 'LoadOrCreateTest' ));
        $this->assertResultFail($ret);
        $this->assertNull($b->id);
    }

    public function testRecordRawCreateBook()
    {
        $b = new \AuthorBooks\Model\Book ;
        $ret = $b->rawCreate(array( 'title' => 'Go Programming' ));
        $this->assertResultSuccess($ret);
        ok($b->id);
        is(RESULT::TYPE_CREATE, $ret->type);        
        $this->successfulDelete($b);
    }

    public function testRecordRawUpdateBook()
    {
        $b = new \AuthorBooks\Model\Book ;
        ok($b);
        $ret = $b->rawCreate(array( 'title' => 'Go Programming without software validation' ));
        $this->assertResultSuccess($ret);
        ok($b->id);
        $ret = $b->rawUpdate(array( 'title' => 'Perl Programming without filtering' ));
        $this->assertResultSuccess($ret);
        ok($b->id);
        is(RESULT::TYPE_UPDATE, $ret->type);
        $this->successfulDelete($b);
    }


    public function testLoadOrCreateModel() 
    {
        $results = array();
        $b = new \AuthorBooks\Model\Book ;

        $ret = $b->create(array( 'title' => 'Should Create, not load this' ));
        $this->assertResultSuccess($ret);
        $results[] = $ret;

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        $this->assertResultSuccess($ret);
        $results[] = $ret;

        $id = $b->id;
        ok($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        $this->assertResultSuccess($ret);
        is($id, $b->id, 'is the same ID');
        is(RESULT::TYPE_LOAD, $ret->type);
        $results[] = $ret;


        $b2 = new \AuthorBooks\Model\Book ;
        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        $this->assertResultSuccess($ret);
        is($id,$b2->id);
        $results[] = $ret;

        $ret = $b2->loadOrCreate( array( 'title' => 'LoadOrCreateTest2'  ) , 'title' );
        $this->assertResultSuccess($ret);
        is(RESULT::TYPE_CREATE, $ret->type);
        ok($id != $b2->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3 = new \AuthorBooks\Model\Book ;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        $this->assertResultSuccess($ret);
        ok($id != $b3->id , 'we should create anther one'); 
        $results[] = $ret;

        $this->successfulDelete($b3);

        foreach($results as $r ) {
            $book = new Book();
            $book->load(intval($r->id));
            if ($book->id) {
                $book->delete();
            }
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

    public function testModelUpdateRaw() 
    {
        $author = new \AuthorBooks\Model\Author;
        $ret = $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);

        $ret = $author->update(array('id' => new Raw('id + 3') ));
        $this->assertResultSuccess($ret);
        is(RESULT::TYPE_UPDATE, $ret->type);
    }

    public function testManyToManyRelationRecordCreate()
    {
        $author = new \AuthorBooks\Model\Author;
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
        $this->successfulDelete($author);
    }


    /**
     * @rebuild false
     */
    public function testPrimaryKeyIdIsInteger()
    {
        $author = new \AuthorBooks\Model\Author;
        $ret = $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertResultSuccess($ret);

        // XXX: in different database engine, it's different.
        // sometimes it's string, sometimes it's integer
        // ok( is_string( $author->getValue('id') ) );
        ok(is_integer($author->get('id')));
        $this->successfulDelete($author);
    }


    public function testManyToManyRelationFetchRecord()
    {
        $author = new \AuthorBooks\Model\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        $book = $author->books->create(array( 'title' => 'Book Test' ));
        ok( $book );
        ok( $book->id , 'book is created' );

        $ret = $book->delete();
        ok( $ret->success );
        is(RESULT::TYPE_DELETE, $ret->type);

        $ab = new \AuthorBooks\Model\AuthorBook;
        $book = new \AuthorBooks\Model\Book ;

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
        $author = new \AuthorBooks\Model\Author;
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
        $author = new \AuthorBooks\Model\Author;
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
        $author = new \AuthorBooks\Model\Author;
        ok( $author );

        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        $address = new \AuthorBooks\Model\Address;
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


    /**
     * @basedata false
     */
    public function testRecordUpdateWithRawSQL()
    {
        $n = new \AuthorBooks\Model\Book ;
        $n->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        is( 0 , $n->view );
        $ret = $n->update(array( 
            'view' => new Raw('view + 1')
        ), array('reload' => true));

        ok($ret->success, $ret->message);
        is(1, $n->view);

        $n->update(array( 
            'view' => new Raw('view + 3'),
        ), array('reload' => true));
        $ret = $n->reload();
        ok( $ret->success );
        is( 4, $n->view );

        result_ok($n->delete());
    }



    /**
     * @rebuild false
     */
    public function testZeroInflator()
    {
        $b = new \AuthorBooks\Model\Book ;
        $ret = $b->create(array( 'title' => 'Zero number inflator' , 'view' => 0 ));
        $this->assertResultSuccess($ret);
        ok($b->id);
        is( 0 , $b->view );

        $ret = $b->load($ret->id);
        $this->assertResultSuccess($ret);
        ok($b->id);
        is( 0 , $b->view );
        $this->successfulDelete($b);
    }


    /**
     * @rebuild false
     */
    public function testUpdateWithReloadOption()
    {
        $b = new \AuthorBooks\Model\Book ;
        $ret = $b->create(array( 'title' => 'Create for reload test' , 'view' => 0 ));
        $this->assertResultSuccess($ret);

        // test incremental with Raw statement
        $ret = $b->update(array( 'view'  => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        is(1,  $b->view);

        $ret = $b->update(array('view' => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        is( 2,  $b->view );

        $ret = $b->delete();
        $this->assertResultSuccess($ret);
    }
}


