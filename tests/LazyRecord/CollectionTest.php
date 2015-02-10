<?php
use LazyRecord\SqlBuilder;
use AuthorBooks\Model\Book;
use AuthorBooks\Model\BookCollection;
use AuthorBooks\Model\Author;
use AuthorBooks\Model\AuthorCollection;
use LazyRecord\Testing\ModelTestCase;

class CollectionTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return ['TestApp\Model\NameSchema'];
    }

    public function testBooleanType()
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

        ok($items = $names->items() , 'Test name collection with name,address condition' );
        ok($size = $names->size());
        is(1 , $size);
        is('Foo', $items[0]->name);
    }
}

