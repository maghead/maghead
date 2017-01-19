<?php
use SQLBuilder\Raw;
use LazyRecord\Testing\ModelTestCase;
use LazyRecord\Result;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\Address;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\AuthorBook;
/**
 * Testing models:
 *   1. Author
 *   2. Book
 *   3. Address
 */
class BasicCRUDTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\BookSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\AddressSchema,
        ];
    }

    public function setUp() {
        if (! extension_loaded('pdo_' . $this->driver)) {
            $this->markTestSkipped('pdo_' . $this->driver . ' extension is required for model testing');
            return;
        }
        parent::setUp();
    }

    /**
     * @expectedException PDOException
     */
    public function testTitleIsRequired()
    {
        $b = new Book;
        $ret = $b->load(array( 'name' => 'LoadOrCreateTest' ));
        $this->assertResultFail($ret);
        $this->assertNull($b->id);
    }


    public function testRecordRawCreateBook()
    {
        $b = new Book;
        $ret = $b->rawCreate(array( 'title' => 'Go Programming' ));
        $this->assertResultSuccess($ret);
        $this->assertNotNull($b->id);
        $this->assertEquals(Result::TYPE_CREATE, $ret->type);
        $this->successfulDelete($b);
    }


    public function testRecordRawUpdateBook()
    {
        $b = new \AuthorBooks\Model\Book;
        $ret = $b->rawCreate(array( 'title' => 'Go Programming without software validation' ));
        $this->assertResultSuccess($ret);
        $this->assertNotNull($b->id);
        $ret = $b->rawUpdate(array( 'title' => 'Perl Programming without filtering' ));
        $this->assertResultSuccess($ret);
        $this->assertNotNull($b->id);
        $this->assertEquals(Result::TYPE_UPDATE, $ret->type);
        $this->successfulDelete($b);
    }


    public function testFind()
    {
        $results = array();
        $book1 = Book::createAndLoad(array( 'title' => 'Book1' ));
        $this->assertNotFalse($book1);

        $book2 = Book::createAndLoad(array( 'title' => 'Book2' ));
        $this->assertNotFalse($book2);

        $findBook = new Book;
        $found = Book::defaultRepo()->find($book1->id);
        $this->assertNotFalse($found);
        $this->assertInstanceOf('AuthorBooks\Model\Book', $found);
        $this->assertEquals($book1->id, $found->id);


        $found = Book::defaultRepo()->find($book2->id);
        $this->assertNotFalse($found);
        $this->assertInstanceOf('AuthorBooks\Model\Book', $found);
        $this->assertEquals($book2->id, $found->id);
    }


    public function testLoadOrCreateModel()
    {
        $results = array();
        $b = new \AuthorBooks\Model\Book;

        $ret = $b->create(array( 'title' => 'Should Create, not load this' ));
        $this->assertResultSuccess($ret);
        $results[] = $ret;
        $b = Book::defaultRepo()->find($ret->key);

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        $this->assertResultSuccess($ret);
        $results[] = $ret;
        $b = Book::defaultRepo()->find($ret->key);

        $id = $b->id;
        $this->assertNotNull($id);

        $ret = $b->loadOrCreate( array( 'title' => 'LoadOrCreateTest'  ) , 'title' );
        $this->assertResultSuccess($ret);
        $this->assertEquals($id, $b->id, 'is the same ID');
        $this->assertEquals(Result::TYPE_LOAD, $ret->type);
        $results[] = $ret;

        $b2 = new Book;
        $ret = $b2->loadOrCreate( array('title' => 'LoadOrCreateTest' ) , 'title' );
        $this->assertResultSuccess($ret);
        $this->assertEquals(Result::TYPE_LOAD, $ret->type);
        $this->assertEquals($id,$b2->id);
        $results[] = $ret;

        $ret = $b2->loadOrCreate( array('title' => 'LoadOrCreateTest2'  ) , 'title' );
        $this->assertResultSuccess($ret);
        $this->assertEquals(Result::TYPE_CREATE, $ret->type);
        $this->assertNotEquals($id, $b2->id , 'we should create anther one'); 
        $results[] = $ret;

        $b3 = new Book;
        $ret = $b3->loadOrCreate( array( 'title' => 'LoadOrCreateTest3'  ) , 'title' );
        $this->assertResultSuccess($ret);
        $b3 = Book::defaultRepo()->find($ret->key);
        $this->assertNotEquals($id, $b3->id , 'we should create anther one'); 
        $results[] = $ret;
        $this->successfulDelete($b3);
    }

    public function testRepoWithDataSourceId()
    {
        $repo = Book::repo('default');
        $this->assertInstanceOf('LazyRecord\BaseRepo', $repo);
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
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);
        $author = AUthor::find($ret->key);

        $ret = $author->update(array('id' => new Raw('id + 3') ));
        $this->assertResultSuccess($ret);
        $this->assertEquals(Result::TYPE_UPDATE, $ret->type);
    }

    public function testManyToManyRelationRecordCreate()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertNotNull( 
            $book = $author->books->create(array( 
                'title' => 'Programming Perl I',
                ':author_books' => array( 'created_on' => '2010-01-01' ),
            ))
        );
        $this->assertNotNull($book->id);
        $this->assertEquals( 'Programming Perl I' , $book->title );

        $this->assertEquals( 1, $author->books->size() );
        $this->assertEquals( 1, $author->author_books->size() );
        $this->assertNotNull( $author->author_books[0] );
        $this->assertNotNull( $author->author_books[0]->created_on );
        $this->assertEquals( '2010-01-01', $author->author_books[0]->getCreatedOn()->format('Y-m-d') );

        $author->books[] = array( 
            'title' => 'Programming Perl II',
        );
        $this->assertEquals( 2, $author->books->size() , '2 books' );

        $books = $author->books;
        $this->assertEquals( 2, $books->size() , '2 books' );

        foreach( $books as $book ) {
            $this->assertNotNull( $book->id );
            $this->assertNotNull( $book->title );
        }

        foreach( $author->books as $book ) {
            $this->assertNotNull( $book->id );
            $this->assertNotNull( $book->title );
        }

        $books = $author->books;
        $this->assertEquals( 2, $books->size() , '2 books' );
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

        $author = Author::defaultRepo()->find($ret->key);

        // XXX: in different database engine, it's different.
        // sometimes it's string, sometimes it's integer
        // ok( is_string( $author->getValue('id') ) );
        $this->assertTrue(is_integer($author->getId()));
        $this->successfulDelete($author);
    }


    public function testManyToManyRelationFetchRecord()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        $book = $author->books->create(array( 'title' => 'Book Test' ));
        $this->assertNotNull( $book );
        $this->assertNotNull( $book->id , 'book is created' );

        $ret = $book->delete();
        $this->assertTrue($ret->success);
        $this->assertEquals(Result::TYPE_DELETE, $ret->type);

        $ab = new \AuthorBooks\Model\AuthorBook;
        $book = new \AuthorBooks\Model\Book ;

        // should not include this
        $this->assertNotFalse( $book = Book::createAndLoad(array( 'title' => 'Book I Ex' )) );
        $this->assertNotFalse( $book = Book::createAndLoad(array( 'title' => 'Book I' )) );

        $ret = $ab->create(array(
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));
        $this->assertResultSuccess($ret);
        $ab = AuthorBook::defaultRepo()->find($ret->key);

        $this->assertNotFalse($book = Book::createAndLoad(array( 'title' => 'Book II' )) );
        $ab = AuthorBook::createAndLoad([
            'author_id' => $author->id,
            'book_id' => $book->id,
        ]);

        $this->assertNotFalse( $book = Book::createAndLoad(array( 'title' => 'Book III' )) );
        $ab = AuthorBook::createAndLoad([
            'author_id' => $author->id,
            'book_id' => $book->id,
        ]);

        // retrieve books from relationshipt
        $author->flushCache();
        $books = $author->books;
        $this->assertEquals(3, $books->size() , 'We have 3 books' );

        $bookTitles = array();
        foreach( $books->items() as $item ) {
            $bookTitles[ $item->title ] = true;
            $item->delete();
        }

        $this->assertCount( 3, array_keys($bookTitles) );
        ok( $bookTitles[ 'Book I' ] );
        ok( $bookTitles[ 'Book II' ] );
        ok( $bookTitles[ 'Book III' ] );
        ok( ! isset($bookTitles[ 'Book I Ex' ] ) );
        $author->delete();
    }

    public function testHasManyRelationCreate2()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        // append items
        $author->addresses[] = array( 'address' => 'Harvard' );
        $author->addresses[] = array( 'address' => 'Harvard II' );

        $this->assertEquals(2, $author->addresses->size() , 'just two item' );

        $addresses = $author->addresses->items();
        $this->assertNotEmpty($addresses);
        $this->assertEquals( 'Harvard' , $addresses[0]->address );

        $a = $addresses[0];
        $this->assertInstanceOf('LazyRecord\BaseModel', $retAuthor = $a->author);
        $this->assertEquals('Z', $retAuthor->name );
        $ret = $author->delete();
        $this->assertResultSuccess($ret);
    }

    /**
     * @rebuild false
     */
    public function testHasManyRelationCreate()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        $address = $author->addresses->create(array(
            'address' => 'farfaraway'
        ));

        $this->assertEquals($author->id, $address->author_id);
        $this->assertEquals('farfaraway', $address->address);
        $ret = $address->delete();
        $this->assertResultSuccess($ret);
        $ret = $author->delete();
        $this->assertResultSuccess($ret);
    }

    public function testHasManyRelationFetch()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertNotFalse($author);

        $address = Address::createAndLoad(array(
            'author_id' => $author->id,
            'address' => 'Taiwan Taipei',
        ));
        $this->assertNotFalse($address);
        $this->assertInstanceOf('LazyRecord\BaseModel' , $address->author);
        $this->assertEquals( $author->id, $address->author->id );

        $ret = $address->create(array(
            'author_id' => $author->id,
            'address' => 'Taiwan Taipei II',
        ));
        $this->assertResultSuccess($ret);

        // xxx: provide getAddresses() method generator
        $addresses = $author->addresses;
        $this->assertInstanceOf('LazyRecord\BaseCollection', $addresses);

        $items = $addresses->items();
        $this->assertNotEmpty($items);
        $this->assertCount(2, $items);
    }


    /**
     * @basedata false
     */
    public function testRecordUpdateWithRawSQL()
    {
        $book = new \AuthorBooks\Model\Book ;
        $ret = $book->create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        $this->assertResultSuccess($ret);

        $book = Book::defaultRepo()->find($ret->key);

        $this->assertEquals( 0 , $book->view );
        $ret = $book->update(array( 
            'view' => new Raw('view + 1')
        ), array('reload' => true));
        $this->assertResultSuccess($ret);
        $this->assertEquals(1, $book->view);

        $ret = $book->update(array(
            'view' => new Raw('view + 3'),
        ), array('reload' => true));
        $this->assertResultSuccess($ret);

        $ret = $book->reload();
        $this->assertResultSuccess($ret);
        $this->assertEquals( 4, $book->view );
        $this->assertResultSuccess($book->delete());
    }



    /**
     * @rebuild false
     */
    public function testZeroInflator()
    {
        $b = new \AuthorBooks\Model\Book ;
        $ret = $b->create(array( 'title' => 'Zero number inflator' , 'view' => 0 ));
        $this->assertResultSuccess($ret);
        $b = Book::defaultRepo()->find($ret->key);
        $this->assertNotNull($b->id);
        $this->assertEquals(0 , $b->view);

        $found = Book::defaultRepo()->find($ret->key);
        $this->assertNotFalse($found);
        $this->assertInstanceOf('AuthorBooks\Model\Book', $found);
        $this->assertEquals(0 , $found->view);
        $this->successfulDelete($found);
    }

    /**
     * @rebuild false
     */
    public function testUpdateWithReloadOption()
    {
        $b = new \AuthorBooks\Model\Book;
        $ret = $b->create(array('title' => 'Create for reload test' , 'view' => 0));
        $this->assertResultSuccess($ret);
        $b = Book::defaultRepo()->find($ret->key);

        // test incremental with Raw statement
        $ret = $b->update(array('view'  => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        $this->assertEquals(1,  $b->view);

        $ret = $b->update(array('view' => new Raw('view + 1') ), array('reload' => true));
        $this->assertResultSuccess($ret);
        $this->assertEquals( 2,  $b->view );

        $ret = $b->delete();
        $this->assertResultSuccess($ret);
    }
}
