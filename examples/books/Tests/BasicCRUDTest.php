<?php

namespace AuthorBooks\Tests;

use SQLBuilder\Raw;
use Maghead\Testing\ModelTestCase;
use Maghead\Runtime\Result;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\Address;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\AuthorBook;

/**
 * Testing models:
 *   1. Author
 *   2. Book
 *   3. Address
 *
 * @group app
 */
class BasicCRUDTest extends ModelTestCase
{
    public function models()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\BookSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\AddressSchema,
            new \AuthorBooks\Model\TagSchema,
        ];
    }

    public function setUp()
    {
        parent::setUp();
    }

    /**
     * @expectedException PDOException
     */
    public function testTitleIsRequired()
    {
        $book = Book::load(array( 'name' => 'LoadOrCreateTest' ));
        $this->assertNotFalse($book);
        $this->assertNull($book->id);
    }


    public function testRecordRawCreateBook()
    {
        $ret = Book::rawCreate(array( 'title' => 'Go Programming' ));
        $this->assertResultSuccess($ret);
        $this->assertEquals(Result::TYPE_CREATE, $ret->type);

        $book = Book::load($ret->key);
        $this->assertNotNull($book->id);
    }

    public function testRecordRawUpdateBook()
    {
        $ret = Book::rawCreate(array( 'title' => 'Go Programming without software validation' ));
        $this->assertResultSuccess($ret);
        $this->assertNotNull($ret->key);


        $book = Book::load($ret->key);
        $ret = $book->rawUpdate(array( 'title' => 'Perl Programming without filtering' ));
        $this->assertResultSuccess($ret);
        $this->assertEquals(Result::TYPE_UPDATE, $ret->type);
    }



    public function testLoadOrCreateModel()
    {
        $results = array();
        $b = new \AuthorBooks\Model\Book;

        $ret = $b->create(array( 'title' => 'Should Create, not load this' ));
        $this->assertResultSuccess($ret);
        $results[] = $ret;
        $b = Book::masterRepo()->load($ret->key);

        $ret = $b->create(array( 'title' => 'LoadOrCreateTest' ));
        $this->assertResultSuccess($ret);
        $results[] = $ret;
        $b = Book::masterRepo()->load($ret->key);

        $id = $b->id;
        $this->assertNotNull($id);

        $b = $b->loadOrCreate(array( 'title' => 'LoadOrCreateTest'  ), 'title');
        $this->assertEquals($id, $b->id, 'is the same ID');
        $results[] = $ret;

        $b2 = new Book;
        $b2 = $b2->loadOrCreate(array('title' => 'LoadOrCreateTest' ), 'title');
        $this->assertEquals($id, $b2->id);
        $results[] = $ret;

        $b2 = $b2->loadOrCreate(array('title' => 'LoadOrCreateTest2'  ), 'title');
        $this->assertNotEquals($id, $b2->id, 'we should create anther one');
        $results[] = $ret;

        $b3 = new Book;
        $b3 = $b3->loadOrCreate(array( 'title' => 'LoadOrCreateTest3'  ), 'title');
        $this->assertNotEquals($id, $b3->id, 'we should create anther one');
        $this->assertDelete($b3);
    }

    public function testCreateRepoWithDataSourceId()
    {
        $repo = Book::repo('master');
        $this->assertInstanceOf('Maghead\Runtime\BaseRepo', $repo);
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
        $ret = Author::create(array(
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->assertResultSuccess($ret);
        $author = Author::masterRepo()->load($ret->key);

        $ret = $author->update(array('id' => new Raw('id + 3') ));
        $this->assertResultSuccess($ret);
        $this->assertEquals(Result::TYPE_UPDATE, $ret->type);
    }

    public function testManyToManyRelationRecordCreate()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'zaa' ));
        $this->assertNotNull(
            $book = $author->books->create(array(
                'title' => 'Programming Perl I',
                'author_books' => array( 'created_on' => '2010-01-01' ),
            ))
        );
        $this->assertNotNull($book->id);
        $this->assertEquals('Programming Perl I', $book->title);

        $this->assertEquals(1, $author->books->size());
        $this->assertEquals(1, $author->author_books->size());
        $this->assertNotNull($author->author_books[0]);
        $this->assertNotNull($author->author_books[0]->created_on);
        $this->assertEquals('2010-01-01', $author->author_books[0]->getCreatedOn()->format('Y-m-d'));

        $author->books[] = array(
            'title' => 'Programming Perl II',
        );
        $this->assertEquals(2, $author->books->size(), '2 books');

        $books = $author->books;
        $this->assertEquals(2, $books->size(), '2 books');

        foreach ($books as $book) {
            $this->assertNotNull($book->id);
            $this->assertNotNull($book->title);
        }

        foreach ($author->books as $book) {
            $this->assertNotNull($book->id);
            $this->assertNotNull($book->title);
        }

        $books = $author->books;
        $this->assertEquals(2, $books->size(), '2 books');
        $this->assertDelete($author);
    }


    /**
     * @rebuild false
     */
    public function testPrimaryKeyIdIsInteger()
    {
        $author = new \AuthorBooks\Model\Author;
        $ret = Author::create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'zaa' ));
        $this->assertResultSuccess($ret);

        $author = Author::masterRepo()->load($ret->key);

        // XXX: in different database engine, it's different.
        // sometimes it's string, sometimes it's integer
        // ok( is_string( $author->getValue('id') ) );
        $this->assertTrue(is_integer($author->getId()));
        $this->assertDelete($author);
    }


    public function testManyToManyRelationFetchRecord()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'zaa' ));

        $book = $author->books->create(array( 'title' => 'Book Test' ));
        $this->assertNotNull($book);
        $this->assertNotNull($book->id, 'book is created');

        $ret = $book->delete();
        $this->assertTrue($ret->success);
        $this->assertEquals(Result::TYPE_DELETE, $ret->type);

        $ab = new \AuthorBooks\Model\AuthorBook;
        $book = new \AuthorBooks\Model\Book ;

        // should not include this
        $this->assertNotFalse($book = Book::createAndLoad(array( 'title' => 'Book I Ex' )));
        $this->assertNotFalse($book = Book::createAndLoad(array( 'title' => 'Book I' )));

        $ret = $ab->create(array(
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));
        $this->assertResultSuccess($ret);
        $ab = AuthorBook::masterRepo()->load($ret->key);

        $this->assertNotFalse($book = Book::createAndLoad(array( 'title' => 'Book II' )));
        $ab = AuthorBook::createAndLoad([
            'author_id' => $author->id,
            'book_id' => $book->id,
        ]);

        $this->assertNotFalse($book = Book::createAndLoad(array( 'title' => 'Book III' )));
        $ab = AuthorBook::createAndLoad([
            'author_id' => $author->id,
            'book_id' => $book->id,
        ]);

        // retrieve books from relationshipt
        $author->flushInternalCache();
        $books = $author->books;
        $this->assertEquals(3, $books->size(), 'We have 3 books');

        $bookTitles = array();
        foreach ($books->items() as $item) {
            $bookTitles[ $item->title ] = true;
            $item->delete();
        }

        $this->assertCount(3, array_keys($bookTitles));
        $this->assertNotNull($bookTitles[ 'Book I' ]);
        $this->assertNotNull($bookTitles[ 'Book II' ]);
        $this->assertNotNull($bookTitles[ 'Book III' ]);
        $this->assertFalse(isset($bookTitles[ 'Book I Ex' ]));
        $author->delete();
    }

    /**
     * @basedata false
     */
    public function testRecordUpdateWithRawSQL()
    {
        $ret = Book::create(array(
            'title' => 'book title',
            'view' => 0,
        ));
        $this->assertResultSuccess($ret);

        $book = Book::masterRepo()->load($ret->key);

        $this->assertEquals(0, $book->view);
        $ret = $book->update([
            'view' => new Raw('view + 1')
        ]);
        $this->assertResultSuccess($ret);

        $book = Book::masterRepo()->load($ret->key);
        $this->assertEquals(1, $book->view);

        $ret = $book->update([
            'view' => new Raw('view + 3'),
        ]);
        $this->assertResultSuccess($ret);

        $book = Book::masterRepo()->load($ret->key);
        $this->assertResultSuccess($ret);
        $this->assertEquals(4, $book->view);
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
        $b = Book::masterRepo()->load($ret->key);
        $this->assertNotNull($b->id);
        $this->assertEquals(0, $b->view);

        $found = Book::masterRepo()->load($ret->key);
        $this->assertNotFalse($found);
        $this->assertInstanceOf('AuthorBooks\Model\Book', $found);
        $this->assertEquals(0, $found->view);
        $this->assertDelete($found);
    }
}
