<?php
use Maghead\SqlBuilder;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\Address;
use AuthorBooks\Model\AuthorCollection;
use Maghead\Testing\ModelTestCase;
use Maghead\Exporter\CSVExporter;
use Maghead\Importer\CSVImporter;

class AuthorFactory {

    static function create($name) {
        return Author::createAndLoad(array(
            'name' => $name,
            'email' => 'temp@temp' . rand(),
            'identity' => rand(),
            'confirmed' => true,
        ));
    }

}

class AuthorCollectionTest extends ModelTestCase
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

    public function testCollectionAsPairs()
    {
        $address = new \AuthorBooks\Model\Address;
        $results = array();
        $results[] = $ret = $address->create(array( 'address' => 'Hack' ));
        $this->assertResultSuccess($ret);
        $this->assertResultSuccess($results[] = $address->create(array( 'address' => 'Hack I' )));
        $this->assertResultSuccess($results[] = $address->create(array( 'address' => 'Hack II' )));

        $addresses = new \AuthorBooks\Model\AddressCollection;
        $pairs = $addresses->asPairs( 'id' , 'address' );
        $this->assertNotEmpty($pairs);

        // Run update
        $addresses->where(array('address' => 'Hack'));
        $ret = $addresses->update(array('address' => 'BooBoo'));
        $this->assertResultSuccess($ret);
        foreach ($results as $result) {
            $id = $result->id;
            ok($id);

            $this->assertTrue(isset($pairs[$id]));
            like('/Hack/',$pairs[$id]);
            $address = Address::load($result->id);
            $address->delete();
        }
    }

    public function testCollectionReset()
    {
        $book = new Book;
        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book I' )));
        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book II' )));

        $books = new \AuthorBooks\Model\BookCollection;
        $books->fetch();
        $this->assertEquals(2,$books->size());

        $this->assertResultSuccess(Book::create(array( 'title' => 'My Book III' )));
        $books->reset();
        $books->fetch();
        $this->assertEquals(3,$books->size());
    }


    /**
     * @rebuild false
     */
    public function testClone()
    {
        $authors = new AuthorCollection;
        $authors->fetch();

        $clone = clone $authors;
        $this->assertTrue($clone !== $authors);
        $this->assertNotSame($clone->getCurrentReadQuery(), $authors->getCurrentReadQuery());
    }

    public function testCloneWithQuery() 
    {
        $a = new Address;
        $ret = Address::create(array('address' => 'Cindy'));
        $this->assertResultSuccess($ret);

        $ret = Address::create(array('address' => 'Hack'));
        $this->assertResultSuccess($ret);

        $addresses = new \AuthorBooks\Model\AddressCollection;
        $addresses->where()->equal('address','Cindy');
        $addresses->fetch();
        $this->assertEquals(1, $addresses->size());

        $sql1 = $addresses->toSQL();
        
        $clone = clone $addresses;
        $sql2 = $clone->toSQL();

        is($sql1, $sql2);
        is(1,$clone->size());

        $clone->free();
        $clone->where()
            ->equal('address','Hack');
        is(0,$clone->size());
    }

    public function testIterator()
    {
        $authors = new AuthorCollection;
        ok($authors);
        foreach( $authors as $a ) {
            ok($a->id);
        }
    }

    public function testBooleanCondition() 
    {
        $a = new Author;
        $ret = Author::create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        ));
        $this->assertResultSuccess($ret);

        $a = new Author;
        $ret = Author::create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'b',
            'confirmed' => true,
        ));
        $this->assertResultSuccess($ret);

        $authors = new AuthorCollection;
        $authors->where()
                ->equal('confirmed', false);
        $ret = $authors->fetch();
        ok($ret);
        $this->assertEquals(1, $authors->size());


        $authors = new AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', true);
        $ret = $authors->fetch();
        ok($ret);
        $this->assertEquals(1,$authors->size());
    }

    public function testCollection()
    {
        $author = new Author;
        foreach( range(1,3) as $i ) {
            $ret = Author::create(array(
                'name' => 'Bar-' . $i,
                'email' => 'bar@bar' . $i,
                'identity' => 'bar' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            $this->resultOK( true, $ret );
        }

        foreach( range(1,20) as $i ) {
            $ret = Author::create(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            $this->resultOK( true, $ret );
        }

        $authors2 = new AuthorCollection;
        $authors2->where()
                ->like('name','Foo');
        $count = $authors2->queryCount();
        $this->assertEquals(20 , $count);

        $authors = new AuthorCollection;
        $authors->where()->like('name','Foo');

        $items = $authors->items();
        $this->assertEquals(20, $authors->size());

        $this->assertTrue(is_array($items));
        foreach($items as $item) {
            ok( $item->id );
            $this->assertInstanceOf('AuthorBooks\Model\Author', $item);

            $ret = $item->delete();
            $this->assertTrue($ret->success);
        }
        $size = $authors->free()->size();
        $this->assertEquals( 0, $size );

        {
            $authors = new AuthorCollection;
            foreach( $authors as $a ) {
                $a->delete();
            }
        }
    }

    public function testJoin()
    {
        $authors = new AuthorCollection;
        $authors->join(new \AuthorBooks\Model\Address, 'LEFT', 'a');
        $authors->fetch();
        $sql = $authors->toSQL();
        like('/LEFT JOIN addresses AS a ON \(m.id = a.author_id\)/', $sql);
    }

    public function testJoinWithAliasAndRelationId() {
        $author = AuthorFactory::create('John');
        $author->addresses[] = array( 'address' => 'Address I' );
        $author->addresses[] = array( 'address' => 'Address II' );

        $authors = new AuthorCollection;
        $authors->join(new \AuthorBooks\Model\Address, 'LEFT', 'a', 'addresses');
        $authors->fetch();
        $sql = $authors->toSQL();
        ok($sql, $sql);

        $size = $authors->size();
        $this->assertEquals(2,$size);
        foreach ($authors as $a) {
            ok($a->a_address);
            ok($a->a_id);
        }
    }

    /**
     * @rebuild false
     */
    public function testJoinWithAliasAndWithoutRelationId() {
        $authors = new AuthorCollection;
        $authors->join(new \AuthorBooks\Model\Address ,'LEFT','a');
        $authors->fetch();
        $sql = $authors->toSQL();
        ok($sql);
    }

    /**
     * @rebuild false
     */
    public function testMeta()
    {
        $authors = new AuthorCollection;
        $this->assertEquals('AuthorBooks\Model\AuthorSchemaProxy', $authors::SCHEMA_PROXY_CLASS);
        $this->assertEquals('AuthorBooks\Model\Author', $authors::MODEL_CLASS);
    }


    public function testFilter() 
    {
        $book = new \AuthorBooks\Model\Book;
        $results = array();
        result_ok( $results[] = Book::create(array( 'title' => 'My Book I' )) );
        result_ok( $results[] = Book::create(array( 'title' => 'My Book II' )) );
        result_ok( $results[] = Book::create(array( 'title' => 'Perl Programming' )) );
        result_ok( $results[] = Book::create(array( 'title' => 'My Book IV' )) );

        $books = new \AuthorBooks\Model\BookCollection;
        $books->fetch();
        count_ok(4, $books);

        $perlBooks = $books->filter(function($item) {
            return $item->title == 'Perl Programming';
        });

        ok($perlBooks);
        is(1, $perlBooks->size());
        count_ok(1, $perlBooks->items());

        foreach( $results as $result ) {
            ok($result->key);
            $record = Book::load($result->id);
            $record->delete();
        }

        $someBooks = $books->splice(0,2);
        is(2, count($someBooks));
    }

    public function testCollectionExporter()
    {
        foreach( range(1,10) as $i ) {
            $author = Author::createAndLoad(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ));
            $this->assertNotFalse($author);
            $this->assertTrue($author->isConfirmed() , 'is true');
        }

        @mkdir('tests/tmp', 0755, true);
        $fp = fopen('tests/tmp/authors.csv', 'w');
        $exporter = new CSVExporter($fp);
        $exporter->exportCollection(new AuthorCollection);
        fclose($fp);

        $authors = new AuthorCollection;
        $authors->delete();

        $fp = fopen('tests/tmp/authors.csv', 'r');
        $importer = new CSVImporter(new Author);
        $importer->importResource($fp);
        fclose($fp);
    }


    public function testCollectionPagerAndSelection()
    {
        $author = new Author;
        foreach( range(1,10) as $i ) {
            $author = Author::createAndLoad(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ));
            ok($author->isConfirmed(), 'is true');
        }


        $authors = new AuthorCollection;
        $authors->where()
                ->equal( 'confirmed' , true );

        foreach( $authors as $author ) {
            ok($author->isConfirmed());
        }
        is( 10, $authors->size() ); 

        /* page 1, 10 per page */
        $pager = $authors->pager(1,10);
        ok( $pager );

        $pager = $authors->pager();
        ok( $pager );
        ok( $pager->items() );

        $array = $authors->toArray();
        ok( $array[0] );
        ok( $array[9] );

        ok( $authors->items() );
        is( 10 , count($authors->items()) );
        foreach( $authors as $a ) {
            $ret = $a->delete();
            ok( $ret->success );
        }
    }
}

