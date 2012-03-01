<?php
use Lazy\SchemaSqlBuilder;

class Collection2Test extends PHPUnit_Framework_ModelTestCase
{

    public function getModels()
    {
        return array( 
            '\tests\AuthorSchema', 
            '\tests\BookSchema',
            '\tests\AuthorBookSchema',
            '\tests\NameSchema',
        );
    }

    public function testIterator()
    {
        $authors = new \tests\AuthorCollection;
        ok( $authors );
        foreach( $authors as $a ) {
            ok( $a->id );
        }
    }

    public function testCollection()
    {
        $author = new \tests\Author;
        foreach( range(1,20) as $i ) {
            $ret = $author->create(array(
                'name' => 'Foo-' . $i,
                'email' => 'foo@foo' . $i,
                'identity' => 'foo' . $i,
                'confirmed' => $i % 2 ? true : false,
            ));
            ok( $ret->success );
        }

        $authors = new \tests\AuthorCollection;
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
    }


    function testBooleanType()
    {
        $name = new \tests\Name;
        $ret = $name->create(array( 
            'name' => 'Foo',
            'confirmed' => false,
            'country' => 'Tokyo',
        ));
        ok( $ret->success );
        is( false, $name->confirmed );

        $ret = $name->load( array( 'name' => 'Foo' ));
        ok( $ret->success );
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
        $names->where()
            ->equal('name','Foo')
            ->groupBy('name','address');

        ok( $items = $names->items() );
        ok( $size = $names->size() );
        is( 1 , $size );
        is( 'Foo', $items[0]->name );
    }

    function testMeta()
    {
        $authors = new \tests\AuthorCollection;
        ok( $authors::schema_proxy_class );
        ok( $authors::model_class );
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
            ok( $ret->success );
        }


        $authors = new \tests\AuthorCollection;
        $authors->where()
                ->equal( 'confirmed' , true );


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

        ok( $authors->items() );
        is( 10 , count($authors->items()) );
        foreach( $authors as $a ) {
            $ret = $a->delete();
            ok( $ret->success );
        }


    }
}

