<?php
use LazyRecord\Testing\ModelTestCase;
use AuthorBooks\Model\Address;
use AuthorBooks\Model\Author;

class AuthorAddressModelTest extends ModelTestCase
{
    public function getModels()
    {
        return array(
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AddressSchema,
        );
    }

    public function testHasManyRelationFetch()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertNotFalse($author);

        $address = Address::createAndLoad(array(
            'author_id' => $author->id,
            'address' => 'Taiwan Taipei',
        ));
        $this->assertNotFalse($address);
        
        $this->assertNotNull($address->author_id);
        $this->assertNotNull($address->author, 'has many relation fetch');
        $this->assertNotNull($address->author->getId());
        $this->assertEquals($author->id, $address->author->id);

        $address = Address::createAndLoad(array( 
            'author_id' => $author->id,
            'address' => 'Taiwan Taipei II',
        ));
        $this->assertNotFalse($address);

        // xxx: provide getAddresses() method generator
        $addresses = $author->addresses;
        $this->assertCollectionSize(2, $addresses);

        $items = $addresses->items();
        $this->assertNotEmpty($items);

        ok($addresses[0]);
        ok($addresses[1]);
        ok(! isset($addresses[2]));
        ok(! @$addresses[2]);

        ok($addresses[0]->id);
        ok($addresses[1]->id);
        $this->assertCount(2 , $addresses);

        /*
        foreach($author->addresses as $ad) {
            $this->assertResultSuccess($ad->delete());
        }
        $this->assertResultSuccess($author->delete());
        */
    }


    /**
     * @basedata false
     */
    public function testHasManyRelationCreate()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertNotFalse($author);
        $this->assertNotNull($author->id);

        $address = $author->addresses->create(array(
            'address' => 'farfaraway'
        ));

        $this->assertNotNull($address->id);
        $this->assertNotNull($address->author_id);
        $this->assertEquals( $author->id, $address->author_id );

        $this->assertEquals('farfaraway' , $address->address);
        $this->assertResultSuccess($address->delete());
        $this->assertResultSuccess($author->delete());
    }

    /**
     * @rebuild false
     * @basedata false
     */
    public function testHasManyRelationCreate2()
    {
        $author = Author::createAndLoad(array( 'name' => 'Z' , 'email' => 'z@z' , 'identity' => 'z' ));
        $this->assertNotFalse($author);

        // append items
        $author->addresses->createAndAppend(['address' => 'Harvard']);
        $author->addresses->createAndAppend(['address' => 'Harvard II']);

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
}
