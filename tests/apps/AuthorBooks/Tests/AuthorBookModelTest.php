<?php
use Maghead\Testing\ModelTestCase;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\AuthorBook;
use AuthorBooks\Model\AuthorBookSchema;
use AuthorBooks\Model\AuthorCollection;
use SQLBuilder\Raw;

class AuthorBookModelTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\BookSchema,
        ];
    }

    /**
     * @basedata false
     */
    public function testBooleanCreate()
    {
        $a = new Author;
        $ret = Author::create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => true,
        ));
        $this->resultOK(true,$ret);

        $a = Author::load($ret->key);
        $this->assertTrue($a->isConfirmed(), 'confirmed should be true');

        $a = Author::load([ 'name' => 'a' ]);
        $this->assertNotNull($a->id);
        $this->resultOK(true,$ret);
        $this->assertTrue($a->isConfirmed());
    }

    /**
     * @basedata false
     */
    public function testBooleanCondition() 
    {
        $a = new Author;
        $ret = Author::create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        ));
        $this->resultOK(true,$ret);

        $a = Author::load($ret->key);
        $this->assertFalse($a->isConfirmed());

        $ret = Author::create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'b',
            'confirmed' => true,
        ));
        $this->resultOK(true,$ret);

        $a = Author::defaultRepo()->load($ret->key);

        $this->assertTrue($a->isConfirmed());

        $authors = new AuthorCollection;
        $this->assertEquals(2,$authors->size(), 'created two authors');


        // test collection query with boolean value
        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', false);
        $ret = $authors->fetch();
        ok($ret);
        is(1,$authors->size());

        $authors = new \AuthorBooks\Model\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', true);
        $ret = $authors->fetch();
        ok($ret);
        is(1,$authors->size());
        $authors->delete();
    }


    /**
     * @rebuild false
     */
    public function testSchemaInterface()
    {
        $author = new Author;
        $names = array('updated_on','created_on','id','name','email','identity','confirmed');
        foreach( $author->getColumnNames() as $n ) {
            ok( in_array( $n , $names ));
            ok( $author->getColumn( $n ) );
        }

        $columns = $author->getColumns();
        $this->assertCount(7 , $columns);

        $columns = $author->getColumns(true); // with virtual column
        count_ok( 8 , $columns );

        ok( 'authors' , $author->getTable() );
        ok( 'Author' , $author->getLabel() );

        $this->assertInstanceOf('AuthorBooks\Model\AuthorCollection' , $author->newCollection());
    }

    /**
     * @rebuild false
     */
    public function testCollection()
    {
        $author = new Author;
        $collection = $author->asCollection();
        ok($collection);
        isa_ok('\AuthorBooks\Model\AuthorCollection',$collection);
    }


    /**
     * @basedata false
     */
    public function testVirtualColumn() 
    {
        $author = new Author;
        $ret = Author::create(array( 
            'name' => 'Pedro' , 
            'email' => 'pedro@gmail.com' , 
            'identity' => 'id',
        ));
        $this->assertResultSuccess($ret);
        $author = Author::defaultRepo()->load($ret->key);

        ok($v = $author->getColumn('account_brief')); // virtual colun
        $this->assertTrue($v->virtual);

        $columns = $author->getSchema()->getColumns();

        ok( ! isset($columns['account_brief']) );

        $this->assertEquals('Pedro(pedro@gmail.com)',$author->account_brief);

        ok( $display = $author->display('account_brief'));
        $authors = new AuthorCollection;
    }

    /**
     * @rebuild false
     */
    public function testSchema()
    {
        $author = new Author;
        ok( $author->getSchema() );

        $columnMap = $author->getSchema()->getColumns();

        ok( isset($columnMap['confirmed']) );
        ok( isset($columnMap['identity']) );
        ok( isset($columnMap['name']) );

        ok( $author::SCHEMA_PROXY_CLASS );

        $columnMap = $author->getColumns();

        ok( isset($columnMap['identity']) );
        ok( isset($columnMap['name']) );
    }



    public function testCreateRecordWithEmptyArguments()
    {
        $author = new Author;
        $ret = Author::create(array());
        $this->assertResultFail($ret);
        is( 'Empty arguments' , $ret->message );
    }


    /**
     * Basic CRUD Test 
     */
    public function testModel()
    {
        $author = new Author;

        $a2 = Author::load(array( 'name' => 'A record does not exist.' ));
        $this->assertFalse($a2);

        $ret = Author::create(array( 'name' => 'long string \'` long string' , 'email' => 'email' , 'identity' => 'id' ));
        ok($ret->success);

        $a2 = Author::load($ret->key);
        ok($a2->id);

        $ret = Author::create(array( 'xxx' => true, 'name' => 'long string \'` long string' , 'email' => 'email2' , 'identity' => 'id2' ));
        ok($ret->success);

        $a2 = Author::load($ret->key);
        ok($a2->id);

        $ret = Author::create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        $this->resultOK(true, $ret);
        ok( $id = $ret->key );
        $author = Author::defaultRepo()->load($ret->key);
        $this->assertEquals( 'Foo', $author->name );
        $this->assertEquals( 'foo@google.com', $author->email );

        $author = Author::load($id);
        $this->assertEquals( $id , $author->id );
        $this->assertEquals( 'Foo', $author->name );
        $this->assertEquals( 'foo@google.com', $author->email );
        $this->assertFalse($author->isConfirmed() );

        $author = Author::load(array( 'name' => 'Foo' ));
        $this->assertEquals( $id , $author->id );
        $this->assertEquals( 'Foo', $author->name );
        $this->assertEquals( 'foo@google.com', $author->email );
        $this->assertFalse($author->isConfirmed());

        $ret = $author->update(array('name' => 'Bar'));
        $this->resultOK(true, $ret);

        $this->assertEquals('Bar', $author->name );

        $ret = $author->delete();
        $this->resultOK(true, $ret);
    }





    public function testUpdateRaw() 
    {
        $author = new Author;
        $ret = Author::create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        result_ok($ret);
        $author = Author::defaultRepo()->load($ret->key);
        $ret = $author->update(array('id' => new Raw('id + 3') ));
        result_ok($ret);
    }

    public function testUpdateNull()
    {
        $author = new Author;
        $ret = Author::create(array(
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->resultOK(true, $ret);

        $author = Author::defaultRepo()->load($ret->key);
        $id = $author->id;

        $ret = $author->update(array( 'name' => 'I' ));
        $this->resultOK(true, $ret);
        $this->assertEquals( $id , $author->id );
        $this->assertEquals( 'I', $author->name );

        $ret = $author->update(array('name' => null));
        $this->resultOK(true, $ret);
        $this->assertEquals($id , $author->id );
        $this->assertEquals(null, $author->name );

        $author = Author::load($author->id);
        $this->assertEquals($id , $author->id );
        $this->assertEquals(null, $author->name );
    }



    public function testJoin()
    {
        $author = new Author;
        $ret = Author::create([
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ]);
        $this->assertResultSuccess($ret);
        $author = Author::defaultRepo()->load($ret->key);

        $ab = new AuthorBook;
        $book = new \AuthorBooks\Model\Book;

        $ret = Book::create(array( 'title' => 'Book I' ));
        $this->assertResultSuccess($ret);
        $book = Book::defaultRepo()->load($ret->key);

        $ret = $ab->create([
            'author_id' => $author->id,
            'book_id' => $book->id,
        ]);
        $this->assertResultSuccess($ret);
        $ab = AuthorBook::defaultRepo()->load($ret->key);

        $ret = Book::create(array( 'title' => 'Book II' ));
        $this->assertResultSuccess($ret);
        $book = Book::defaultRepo()->load($ret->key);

        $ret = $ab->create([
            'author_id' => $author->id,
            'book_id' => $book->id,
        ]);
        $this->assertResultSuccess($ret);
        $ab = AuthorBook::defaultRepo()->load($ret->key);

        $ret = Book::create(array( 'title' => 'Book III' ));
        $this->assertResultSuccess($ret);
        $book = Book::defaultRepo()->load($ret->key);

        $ret = $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));
        $this->assertResultSuccess($ret);

        $books = new \AuthorBooks\Model\BookCollection;
        $books->join('author_books')
            ->as('ab')
                ->on()
                    ->equal( 'ab.book_id' , array('m.id') );
        $books->where()->equal( 'ab.author_id' , $author->id );
        $items = $books->items();
        $this->assertNotEmpty($items);

        $bookTitles = array();
        foreach ($books as $book ) {
            $bookTitles[ $book->title ] = true;
            $ret = $book->delete();
        }

        $this->assertCount(3, array_keys($bookTitles) );
        ok($bookTitles['Book I']);
        ok($bookTitles['Book II' ]);
        ok($bookTitles['Book III']);
    }


    public function testManyToManyAccessor()
    {
        $author = Author::createAndLoad(array('name' => '高德纳' , 'email' => 'donald@no-email.cloud' , 'identity' => 'donald_knuth'));
        $this->assertNotFalse($author);
        $this->assertNotNull($author->getKey());

        $book1 = Book::createAndLoad(['title' => 'The Art of Computer Programming I']);
        $this->assertNotFalse($book1);
        $this->assertNotNull($book1->getKey());

        $book2 = Book::createAndLoad(['title' => 'The Art of Computer Programming II']);
        $this->assertNotFalse($book2);
        $this->assertNotNull($book2->getKey());

        $book3 = Book::createAndLoad(['title' => 'Nothing']);
        $this->assertNotFalse($book3);
        $this->assertNotNull($book3->getKey());

        $ret = AuthorBook::create([ 'author_id' => $author->getKey(), 'book_id' => $book1->getKey() ]);
        $this->assertResultSuccess($ret);

        $ret = AuthorBook::create([ 'author_id' => $author->getKey(), 'book_id' => $book2->getKey() ]);
        $this->assertResultSuccess($ret);

        $books = $author->getBooks();
        $this->assertNotFalse($books);
        $this->assertInstanceOf('AuthorBooks\\Model\\BookCollection', $books);
        $this->assertCount(2, $books);
    }

    public function testManyToManyRelationCreate()
    {
        $ret = Author::create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertResultSuccess($ret);
        $author = Author::load($ret->key);

        ok(
            $book = $author->books->create(array(
                'title' => 'Programming Perl I',
                'author_books' => ['created_on' => '2010-01-01'],
            ))
        );
        ok( $book->id );
        $this->assertEquals( 'Programming Perl I' , $book->title );

        $this->assertEquals( 1, $author->books->size() );
        $this->assertEquals( 1, $author->author_books->size() );
        ok( $author->author_books[0] );
        ok( $author->author_books[0]->created_on );
        $this->assertEquals('2010-01-01', $author->author_books[0]->getCreatedOn()->format('Y-m-d'));

        $author->books[] = array( 
            'title' => 'Programming Perl II',
        );
        $this->assertEquals( 2, $author->books->size() , '2 books' );

        $books = $author->books;
        $this->assertEquals( 2, $books->size() , '2 books' );

        foreach( $books as $book ) {
            $this->assertNotNull($book->id);
            $this->assertNotNull($book->title);
        }

        foreach( $author->books as $book ) {
            $this->assertNotNull( $book->id );
            $this->assertNotNull( $book->title );
        }

        $books = $author->books;
        $this->assertEquals( 2, $books->size() , '2 books' );
        $author->delete();
    }



    public function testManyToManyRelationFetch()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        // XXX: in different database engine, it's different.
        // sometimes it's string, sometimes it's integer
        // ok( is_string( $author->getValue('id') ) );
        ok($author->getId());

        $book = $author->books->create(array( 'title' => 'Book Test' ));
        ok( $book );
        ok( $book->id , 'book is created' );

        $ret = $book->delete();
        ok( $ret->success );

        $ab = new \AuthorBooks\Model\AuthorBook;
        $book = new \AuthorBooks\Model\Book;

        // should not include this
        ok( $book = Book::createAndLoad(array( 'title' => 'Book I Ex' )) );
        ok( $book = Book::createAndLoad(array( 'title' => 'Book I' )) );

        ok($ab = AuthorBook::createAndLoad(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        )));

        ok( $book = Book::createAndLoad(array( 'title' => 'Book II' )) );
        $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));

        ok( $book = Book::createAndLoad(array( 'title' => 'Book III' )) );
        $ab = AuthorBook::createAndLoad(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ));

        // retrieve books from relationshipt
        $author->flushCache();
        $books = $author->books;
        $this->assertEquals( 3, $books->size() , 'We have 3 books' );


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

}

