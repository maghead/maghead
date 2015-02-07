<?php
use LazyRecord\SqlBuilder;
use TestApp\Model\Book;
use TestApp\Model\BookCollection;

class AuthorFactory {

    static function create($name) {
        $author = new \TestApp\Model\Author;
        $author->create(array(
            'name' => $name,
            'email' => 'temp@temp' . rand(),
            'identity' => rand(),
            'confirmed' => true,
        ));
        return $author;
    }

}

class CollectionTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array( 
            'TestApp\Model\AuthorSchema', 
            'TestApp\Model\BookSchema',
            'TestApp\Model\AuthorBookSchema',
            'TestApp\Model\NameSchema',
            'TestApp\Model\AddressSchema',
        );
    }

    public function testCollectionLazyAttributes()
    {
        $authors = new \TestApp\Model\AuthorCollection;
        ok( $authors->_query , 'has lazy attribute' );
    }


    public function testCollectionAsPairs()
    {
        $address = new \TestApp\Model\Address;
        $results = array();
        result_ok( $results[] = $address->create(array( 'address' => 'Hack' )) );
        result_ok( $results[] = $address->create(array( 'address' => 'Hack I' )) );
        result_ok( $results[] = $address->create(array( 'address' => 'Hack II' )) );

        $addresses = new \TestApp\Model\AddressCollection;
        $pairs = $addresses->asPairs( 'id' , 'address' );
        ok( $pairs );

        // Run update
        $addresses->where(array( 'address' => 'Hack' ));
        $ret = $addresses->update(array( 'address' => 'BooBoo' ));
        result_ok($ret);

        foreach( $results as $result ) {
            $id = $result->id;
            ok($id);
            ok(isset($pairs[$id]));
            like('/Hack/',$pairs[$id]);
            $address = new \TestApp\Model\Address($result->id);
            $address->delete();
        }
    }

    public function testReset()
    {
        $results = array();
        $book = new \TestApp\Model\Book;
        result_ok( $results[] = $book->create(array( 'title' => 'My Book I' )) );
        result_ok( $results[] = $book->create(array( 'title' => 'My Book II' )) );

        $books = new \TestApp\Model\BookCollection;
        $books->fetch();
        is(2,$books->size());

        ok( $book->create(array( 'title' => 'My Book III' ))->success );
        $books->reset();
        $books->fetch();
        is(3,$books->size());

        foreach( $results as $result ) {
            ok( $result->id );

            $record = new \TestApp\Model\Book();
            $record->load($result->id);
            $record->delete();
        }
    }

    public function testClone()
    {
        $authors = new \TestApp\Model\AuthorCollection;
        $authors->fetch();

        $clone = clone $authors;
        ok( $clone !== $authors );
        ok( $clone->_readQuery !== $authors->_readQuery );
    }

    public function testCloneWithQuery() 
    {
        $a = new \TestApp\Model\Address;
        ok( $a->create(array('address' => 'Cindy'))->success );
        ok( $a->create(array('address' => 'Hack'))->success );

        $addresses = new \TestApp\Model\AddressCollection;
        $addresses->where()
            ->equal('address','Cindy');
        $addresses->fetch();
        is(1,$addresses->size());

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
        $authors = new \TestApp\Model\AuthorCollection;
        ok( $authors );
        foreach( $authors as $a ) {
            ok( $a->id );
        }
    }

    public function testBooleanCondition() 
    {
        $a = new \TestApp\Model\Author;
        $ret = $a->create(array(
            'name' => 'a',
            'email' => 'a@a',
            'identity' => 'a',
            'confirmed' => false,
        ));
        $this->resultOK(true,$ret);

        $ret = $a->create(array(
            'name' => 'b',
            'email' => 'b@b',
            'identity' => 'b',
            'confirmed' => true,
        ));
        $this->resultOK(true,$ret);

        $authors = new \TestApp\Model\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', false);
        $ret = $authors->fetch();
        ok($ret);
        is(1,$authors->size());


        $authors = new \TestApp\Model\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', true);
        $ret = $authors->fetch();
        ok($ret);
        is(1,$authors->size());
    }

    public function testCollection()
    {
        $author = new \TestApp\Model\Author;
        ok($author);
        foreach( range(1,3) as $i ) {
            $ret = $author->create(array(
                'name' => 'Bar-' . $i,
                'email' => 'bar@bar' . $i,
                'identity' => 'bar' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            $this->resultOK( true, $ret );
        }

        foreach( range(1,20) as $i ) {
            $ret = $author->create(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            $this->resultOK( true, $ret );
        }

        $authors2 = new \TestApp\Model\AuthorCollection;
        $authors2->where()
                ->like('name','Foo');
        $count = $authors2->queryCount();
        is(20 , $count );

        $authors = new \TestApp\Model\AuthorCollection;
        $authors->where()
                ->like('name','Foo');
        $items = $authors->items();
        $size = $authors->size();

        ok( $size );
        is( 20, $size );
        ok( $items );
        ok( is_array( $items ));
        foreach( $items as $item ) {
            ok( $item->id );
            isa_ok( '\TestApp\Model\Author', $item );
            $ret = $item->delete();
            ok( $ret->success );
        }
        $size = $authors->free()->size();
        is( 0, $size );

        {
            $authors = new \TestApp\Model\AuthorCollection;
            foreach( $authors as $a ) {
                $a->delete();
            }
        }
    }


    function testBooleanType()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array( 
            'name' => 'Foo',
            'confirmed' => false,
            'country' => 'Tokyo',
        ));
        ok($ret->success , $ret);
        is(false, $name->confirmed);

        $ret = $name->load( array( 'name' => 'Foo' ));
        ok( $ret->success , $ret );
        is( false, $name->confirmed );

        $name->update(array( 'confirmed' => true ) );
        is( true, $name->confirmed );

        $name->update(array( 'confirmed' => false ) );
        is( false, $name->confirmed );

        $name->delete();

        ok( $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ))->success );
        ok( $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ))->success );
        ok( $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ))->success );
        ok( $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ))->success );
        ok( $name->create(array( 'name' => 'Foo', 'address' => 'Addr1', 'country' => 'Taiwan' ))->success );

        $names = new \TestApp\Model\NameCollection;
        $names->select( 'name' )->where()
            ->equal('name','Foo');

        $names->groupBy(['name','address']);

        ok( $items = $names->items() , 'Test name collection with name,address condition' );
        ok( $size = $names->size() );
        is( 1 , $size );
        is( 'Foo', $items[0]->name );
    }


    function testJoin()
    {
        $authors = new \TestApp\Model\AuthorCollection;
        ok($authors);

        $authors->join(new \TestApp\Model\Address);

        $authors->fetch();
        $sql = $authors->toSQL();

        like( '/addresses.address\s+AS\s+addresses_address/', $sql );
    }

    function testJoinWithAliasAndRelationId() {
        $author = AuthorFactory::create('John');
        ok($author->id);

        $author->addresses[] = array( 'address' => 'Address I' );
        $author->addresses[] = array( 'address' => 'Address II' );

        $authors = new \TestApp\Model\AuthorCollection;
        ok($authors);
        $authors->join( new \TestApp\Model\Address ,'LEFT','a', 'addresses');
        $authors->fetch();
        $sql = $authors->toSQL();
        ok($sql, $sql);

        $size = $authors->size();
        is(2,$size);
        foreach( $authors as $a ) {
            ok($a->a_address);
            ok($a->a_id);
        }
    }

    function testJoinWithAliasAndWithoutRelationId() {
        $authors = new \TestApp\Model\AuthorCollection;
        ok($authors);
        $authors->join( new \TestApp\Model\Address ,'LEFT','a');
        $authors->fetch();
        $sql = $authors->toSQL();
        ok($sql);
        // is('SELECT m.updated_on, m.created_on, m.id, m.name, m.email, m.identity, m.confirmed, addresses.author_id  AS a_author_id, addresses.address  AS a_address, addresses.foo  AS a_foo, addresses.id  AS a_id FROM authors m  LEFT JOIN addresses a ON (m.id = a.author_id)', $sql );
    }

    function testMeta()
    {
        $authors = new \TestApp\Model\AuthorCollection;
        ok( $authors::schema_proxy_class );
        ok( $authors::model_class );
    }


    function testFilter() 
    {
        $book = new \TestApp\Model\Book;
        $results = array();
        result_ok( $results[] = $book->create(array( 'title' => 'My Book I' )) );
        result_ok( $results[] = $book->create(array( 'title' => 'My Book II' )) );
        result_ok( $results[] = $book->create(array( 'title' => 'Perl Programming' )) );
        result_ok( $results[] = $book->create(array( 'title' => 'My Book IV' )) );

        $books = new \TestApp\Model\BookCollection;
        $books->fetch();
        count_ok( 4, $books);
        ok($books);

        $perlBooks = $books->filter(function($item) { 
            return $item->title == 'Perl Programming';
        });

        ok($perlBooks);
        is(1, $perlBooks->size());
        count_ok(1,$perlBooks->_items);

 
        foreach( $results as $result ) {
            ok( $result->id );
            $record = new Book($result->id);
            $record->delete();
        }

        $someBooks = $books->splice(0,2);
        is( 2, count($someBooks) );
    }


    function testCollectionPagerAndSelection()
    {
        $author = new \TestApp\Model\Author;
        foreach( range(1,10) as $i ) {
            $ret = $author->create(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => true,
            ));
            ok( $author->confirmed , 'is true' );
            ok( $ret->success );
        }


        $authors = new \TestApp\Model\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed' , true );

#          $authors->items();
#          var_dump( $authors->getLastSQL() , $authors->getVars() ); 

        foreach( $authors as $author ) {
            ok( $author->confirmed );
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

