<?php
use LazyRecord\Testing\ModelTestCase;

class AuthorAddressModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array(
            'TestApp\Model\\AuthorSchema', 
            'TestApp\Model\\AddressSchema',
        );
    }

    public function testHasManyRelationFetch()
    {
        $author = new \TestApp\Model\Author;
        ok( $author );

        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        $address = new \TestApp\Model\Address;
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


    /**
     * @basedata false
     */
    public function testHasManyRelationCreate()
    {
        $author = new \TestApp\Model\Author;
        $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok( $author->id );

        $address = $author->addresses->create(array( 
            'address' => 'farfaraway'
        ));

        ok( $address->id );
        ok( $address->author_id );
        is( $author->id, $address->author_id );

        is( 'farfaraway' , $address->address );

        $this->assertResultSuccess($address->delete());
        $this->assertResultSuccess($author->delete());
    }

    /**
     * @basedata false
     */
    public function testHasManyRelationCreate2()
    {
        $author = new \TestApp\Model\Author;
        $ret = $author->create(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        ok($author->id);
        $this->assertResultSuccess($ret);

        // append items
        $author->addresses[] = array( 'address' => 'Harvard' );
        $author->addresses[] = array( 'address' => 'Harvard II' );

        is(2, $author->addresses->size() , 'just two item' );

        $addresses = $author->addresses->items();
        ok( $addresses );
        is( 'Harvard' , $addresses[0]->address );

        $a = $addresses[0];
        ok($retAuthor = $a->author );
        ok($retAuthor->id );
        ok($retAuthor->name );
        is('Z', $retAuthor->name);
        $this->assertResultSuccess($author->delete());
    }

    /**
     * @rebuild false
     */
    public function testGeneralInterface() 
    {
        $a = new \TestApp\Model\Address;
        ok($a->getQueryDriver('default') );
        ok($a->getWriteQueryDriver() );
        ok($a->getReadQueryDriver() );
    }

}
