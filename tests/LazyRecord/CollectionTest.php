<?php
use LazyRecord\SqlBuilder;

class AuthorFactory {

    static function create($name) {
        $author = new \tests\Author;
        $author->create(array(
            'name' => $name,
            'email' => 'temp@temp' . rand(),
            'identity' => rand(),
            'confirmed' => true,
        ));
        return $author;
    }

}

class Collection2Test extends PHPUnit_Framework_ModelTestCase
{

    public function getModels()
    {
        return array( 
            'tests\AuthorSchema', 
            'tests\BookSchema',
            'tests\AuthorBookSchema',
            'tests\NameSchema',
            'tests\AddressSchema',
        );
    }

    public function testLazyAttributes()
    {
        $authors = new \tests\AuthorCollection;
        ok( $authors->_query , 'has lazy attribute' );
    }


    public function testAsPairs()
    {
        $address = new \tests\Address;
        ok( $address->create(array( 'address' => 'Hack' ))->success );
        ok( $address->create(array( 'address' => 'Hack I' ))->success );
        ok( $address->create(array( 'address' => 'Hack II' ))->success );

        $addresses = new \tests\AddressCollection;
        $pairs = $addresses->asPairs( 'id' , 'address' );
        ok( $pairs );

        foreach( $address->flushResults() as $result ) {
            $id = $result->id;
            ok($id);
            ok(isset($pairs[$id]));
            like('/Hack/',$pairs[$id]);
            $address->delete( array('id' => $result->id ) );
        }
    }

    public function testLimit()
    {
        // XXX: this should be tested in pgsql or mysql, sqlite does not support limit/offset syntax
        return; 
        $address = new \tests\Address;
        ok( $address->create(array( 'address' => 'Hack' ))->success );
        ok( $address->create(array( 'address' => 'Hack I' ))->success );
        ok( $address->create(array( 'address' => 'Hack II' ))->success );

        $addresses = new \tests\AddressCollection;
        $addresses->limit(1);
        
        is( 1, $addresses->size() );
        foreach( $address->flushResults() as $result ) {
            $address->delete( array('id' => $result->id ) );
        }
    }

    public function testReset()
    {
        $book = new \tests\Book;
        ok( $book->create(array( 'title' => 'My Book I' ))->success );
        ok( $book->create(array( 'title' => 'My Book II' ))->success );

        $books = new \tests\BookCollection;
        $books->fetch();
        is(2,$books->size());

        ok( $book->create(array( 'title' => 'My Book III' ))->success );
        $books->reset();
        $books->fetch();
        is(3,$books->size());

        foreach( $book->flushResults() as $result ) {
            ok( $result->id );
            ok( \tests\Book::delete($result->id)->execute()->success );
        }
    }

    public function testClone()
    {
        $authors = new \tests\AuthorCollection;
        $authors->fetch();

        $clone = clone $authors;
        ok( $clone !== $authors );
        ok( $clone->_readQuery !== $authors->_readQuery );
    }

    public function testCloneWithQuery() 
    {
        ok( $ret = \tests\Address::delete()->execute() );
        ok( $ret->success , $ret->message );

        $a = new \tests\Address;
        ok( $a->create(array('address' => 'Cindy'))->success );
        ok( $a->create(array('address' => 'Hack'))->success );

        $addresses = new \tests\AddressCollection;
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
        $authors = new \tests\AuthorCollection;
        ok( $authors );
        foreach( $authors as $a ) {
            ok( $a->id );
        }
    }

    public function testBooleanCondition() 
    {
        $a = new \tests\Author;
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

        $authors = new \tests\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', false);
        $ret = $authors->fetch();
        ok($ret);
        is(1,$authors->size());


        $authors = new \tests\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed', true);
        $ret = $authors->fetch();
        ok($ret);
        is(1,$authors->size());
    }

    public function testCollection()
    {
        $author = new \tests\Author;
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

        $authors2 = new \tests\AuthorCollection;
        $authors2->where()
                ->like('name','Foo%');
        $count = $authors2->queryCount();
        is( 20 , $count );

        $authors = new \tests\AuthorCollection;
        $authors->where()
                ->like('name','Foo%');
        $items = $authors->items();
        $size = $authors->size();

        ok( $size );
        is( 20, $size );
        ok( $items );
        ok( is_array( $items ));
        foreach( $items as $item ) {
            ok( $item->id );
            isa_ok( '\tests\Author', $item );
            $ret = $item->delete();
            ok( $ret->success );
        }
        $size = $authors->free()->size();
        is( 0, $size );

        {
            $authors = new \tests\AuthorCollection;
            foreach( $authors as $a ) {
                $a->delete();
            }
        }
    }


    function testBooleanType()
    {
        $name = new \tests\Name;
        $ret = $name->create(array( 
            'name' => 'Foo',
            'confirmed' => false,
            'country' => 'Tokyo',
        ));
        ok( $ret->success , $ret );
        is( false, $name->confirmed );

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

        $names = new \tests\NameCollection;
        $names->select( 'name' )->where()
            ->equal('name','Foo')
            ->groupBy('name','address');
        

        ok( $items = $names->items() , 'Test name collection with name,address condition' );
        ok( $size = $names->size() );
        is( 1 , $size );
        is( 'Foo', $items[0]->name );
    }


    function testJoin()
    {
        $authors = new \tests\AuthorCollection;
        ok($authors);

        $authors->join( new \tests\Address );

        $authors->fetch();
        $sql = $authors->toSQL();

        like( '/addresses.address\s+AS\s+addresses_address/', $sql );
    }

    function testJoinWithAliasAndRelationId() {
        $author = AuthorFactory::create('John');
        ok($author->id);

        $author->addresses[] = array( 'address' => 'Address I' );
        $author->addresses[] = array( 'address' => 'Address II' );

        $authors = new \tests\AuthorCollection;
        ok($authors);
        $authors->join( new \tests\Address ,'LEFT','a', 'addresses');
        $authors->fetch();
        $sql = $authors->toSQL();
        ok($sql);

        $size = $authors->size();
        is(2,$size);
        foreach( $authors as $a ) {
            ok($a->a_address);
            ok($a->a_id);
        }
    }

    function testJoinWithAliasAndWithoutRelationId() {
        $authors = new \tests\AuthorCollection;
        ok($authors);
        $authors->join( new \tests\Address ,'LEFT','a');
        $authors->fetch();
        $sql = $authors->toSQL();
        ok($sql);
        // is('SELECT m.updated_on, m.created_on, m.id, m.name, m.email, m.identity, m.confirmed, addresses.author_id  AS a_author_id, addresses.address  AS a_address, addresses.foo  AS a_foo, addresses.id  AS a_id FROM authors m  LEFT JOIN addresses a ON (m.id = a.author_id)', $sql );
    }

    function testMeta()
    {
        $authors = new \tests\AuthorCollection;
        ok( $authors::schema_proxy_class );
        ok( $authors::model_class );
    }


    function testFilter() 
    {
        $book = new \tests\Book;
        ok( $book->create(array( 'title' => 'My Book I' ))->success );
        ok( $book->create(array( 'title' => 'My Book II' ))->success );
        ok( $book->create(array( 'title' => 'Perl Programming' ))->success );
        ok( $book->create(array( 'title' => 'My Book IV' ))->success );

        $books = new \tests\BookCollection;
        $books->fetch();
        count_ok( 4, $books);
        ok($books);

        $perlBooks = $books->filter(function($item) { 
            return $item->title == 'Perl Programming';
        });

        ok($perlBooks);
        is(1, $perlBooks->size());
        count_ok(1,$perlBooks->_items);

 
        foreach( $book->flushResults() as $result ) {
            ok( $result->id );
            ok( \tests\Book::delete($result->id)->execute()->success );
        }

        $someBooks = $books->splice(0,2);
        is( 2, count($someBooks) );
    }


    function test()
    {
        $author = new \tests\Author;
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


        $authors = new \tests\AuthorCollection;
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

