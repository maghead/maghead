<?php
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use SQLBuilder\Raw;

class AuthorBookModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 
            'AuthorBooks\Model\AuthorSchema', 
            'AuthorBooks\Model\AuthorBookSchema',
            'AuthorBooks\Model\BookSchema',
        );
    }

    /**
     * @basedata false
     */
    public function testBooleanCreate()
    {
        $a = new Author;
        $ret = $a->create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => true,
        ));
        $this->resultOK(true,$ret);
        $this->assertTrue($a->confirmed,'confirmed should be true');
        $a->reload();
        $this->assertTrue($a->confirmed,'confirmed should be true');

        $a = new Author;
        $ret = $a->load([ 'name' => 'a' ]);
        $this->assertNotNull($a->id);
        $this->resultOK(true,$ret);
        $this->assertTrue($a->confirmed);
    }

    /**
     * @basedata false
     */
    public function testBooleanCondition() 
    {
        $a = new Author;
        $ret = $a->create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        ));
        $this->resultOK(true,$ret);
        $this->assertFalse($a->confirmed);

        $ret = $a->create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'b',
            'confirmed' => true,
        ));
        $this->resultOK(true,$ret);
        $this->assertTrue($a->confirmed);

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
        count_ok( 7 , $columns );

        $columns = $author->getColumns(true); // with virtual column 'v'
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
        $ret = $author->create(array( 
            'name' => 'Pedro' , 
            'email' => 'pedro@gmail.com' , 
            'identity' => 'id',
        ));
        $this->assertResultSuccess($ret);

        ok( $v = $author->getColumn('v') ); // virtual colun
        ok( $v->virtual );

        $columns = $author->getSchema()->getColumns();

        ok( ! isset($columns['v']) );

        is('pedro@gmail.compedro@gmail.com',$author->get('v'));

        ok( $display = $author->display( 'v' ) );

        $authors = new AuthorBooks\Model\AuthorCollection;
        ok($authors);
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
        $ret = $author->create(array());
        $this->assertResultFail($ret);
        is( 'Empty arguments' , $ret->message );
    }


    /**
     * Basic CRUD Test 
     */
    public function testModel()
    {
        $author = new Author;

        $a2 = new Author;
        $ret = $a2->load( array( 'name' => 'A record does not exist.' ) );
        ok( ! $ret->success );
        ok( ! $a2->id );

        $ret = $a2->create(array( 'name' => 'long string \'` long string' , 'email' => 'email' , 'identity' => 'id' ));
        ok( $ret->success );
        ok( $a2->id );

        $ret = $a2->create(array( 'xxx' => true, 'name' => 'long string \'` long string' , 'email' => 'email2' , 'identity' => 'id2' ));
        ok( $ret->success );
        ok( $a2->id );


        $ret = $author->create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        $this->resultOK(true, $ret);
        ok( $id = $ret->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );

        $ret = $author->load( $id );
        ok( $ret->success );
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->load(array( 'name' => 'Foo' ));
        ok( $ret->success );
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->update(array('name' => 'Bar'));
        $this->resultOK(true, $ret);

        is( 'Bar', $author->name );

        $ret = $author->delete();
        $this->resultOK(true, $ret);

        $data = $author->toArray();
        ok( empty($data), 'should be empty');
    }





    public function testUpdateRaw() 
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        result_ok($ret);
        $ret = $author->update(array('id' => new Raw('id + 3') ));
        result_ok($ret);
    }

    public function testUpdateNull()
    {
        $author = new Author;
        $ret = $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));
        $this->resultOK(true, $ret);

        $id = $author->id;

        $ret = $author->update(array( 'name' => 'I' ));
        $this->resultOK(true, $ret);
        is( $id , $author->id );
        is( 'I', $author->name );

        $ret = $author->update(array('name' => null));
        $this->resultOK(true, $ret);
        is( $id , $author->id );
        is( null, $author->name );

        $ret = $author->load( $author->id );
        $this->resultOK(true, $ret);
        is( $id , $author->id );
        is( null, $author->name );
    }

    public function testJoin()
    {
        $author = new Author;
        $author->create(array( 
            'name' => 'Mary III',
            'email' => 'zz3@zz3',
            'identity' => 'zz3',
        ));

        $ab = new \AuthorBooks\Model\AuthorBook;
        $book = new \AuthorBooks\Model\Book;

        ok( $book->create(array( 'title' => 'Book I' ))->success );
        ok( $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ))->success );

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

        $books = new \AuthorBooks\Model\BookCollection;
        $books->join('author_books')
            ->as('ab')
                ->on()
                    ->equal( 'ab.book_id' , array('m.id') );
        $books->where()
            ->equal( 'ab.author_id' , $author->id );
        $items = $books->items();

        $bookTitles = array();
        foreach( $items as $item ) {
            $bookTitles[ $item->title ] = true;
            $item->delete();
        }

        count_ok( 3, array_keys($bookTitles) );
        ok( $bookTitles[ 'Book I' ] );
        ok( $bookTitles[ 'Book II' ] );
        ok( $bookTitles[ 'Book III' ] );
    }


    public function testManyToManyRelationCreate()
    {
        $author = new Author;
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
        is( '2010-01-01', $author->author_books[0]->created_on->format('Y-m-d'));

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



    public function testManyToManyRelationFetch()
    {
        $author = new Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));

        // XXX: in different database engine, it's different.
        // sometimes it's string, sometimes it's integer
        // ok( is_string( $author->getValue('id') ) );
        ok( is_integer( $author->get('id') ) );

        $book = $author->books->create(array( 'title' => 'Book Test' ));
        ok( $book );
        ok( $book->id , 'book is created' );

        $ret = $book->delete();
        ok( $ret->success );

        $ab = new \AuthorBooks\Model\AuthorBook;
        $book = new \AuthorBooks\Model\Book;

        // should not include this
        ok( $book->create(array( 'title' => 'Book I Ex' ))->success );

        ok( $book->create(array( 'title' => 'Book I' ))->success );
        ok( $ab->create(array( 
            'author_id' => $author->id,
            'book_id' => $book->id,
        ))->success );

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

}

