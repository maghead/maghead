<?php

class AuthorAddressModelTest extends \LazyRecord\ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'tests\\AuthorSchema', 
            'tests\\AddressSchema',
        );
    }

    public function testHasManyRelationFetch()
    {
        $author = new \tests\Author;
        ok( $author );

        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        $address = new \tests\Address;
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


    public function testHasManyRelationCreate()
    {
        $author = new \tests\Author;
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

    public function testHasManyRelationCreate2()
    {
        $author = new \tests\Author;
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


    public function testGeneralInterface() 
    {
        $a = new \tests\Address;
        ok($a);

        ok( $a->getQueryDriver('default') );
        ok( $a->getWriteQueryDriver() );
        ok( $a->getReadQueryDriver() );

        $query = $a->createQuery();
        ok($query);
        isa_ok('SQLBuilder\QueryBuilder', $query );
    }

}
