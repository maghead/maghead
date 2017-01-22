<?php
use Maghead\Testing\ModelTestCase;
use TestApp\Model\Name;

class NameModelTest extends ModelTestCase
{
    public $driver = 'sqlite';

    public function getModels()
    {
        return array('TestApp\Model\\NameSchema');
    }

    public function nameDataProvider()
    {
        return array( 
            array(array(
                'name' => '中文',
                'country' => 'Tokyo',
                'confirmed' => true,
                'date' => new DateTime('2011-01-01 00:00:00'),
            )),
            array(array(
                'name' => 'Test2',
                'country' => 'Taipei',
                'confirmed' => false,
                'date' => '2011-01-01 00:00:00',
            )),
        );
    }

    public function booleanNullTestDataProvider()
    {
        return array(
              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => NULL ) ),
              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '' ) ),
        );
    }


    public function booleanFalseTestDataProvider()
    {
        return array(
              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 0 ) ),
              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '0' ) ),
              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => false ) ),
              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'false' ) ),
        );
    }

    public function booleanTrueTestDataProvider()
    {
        return array(
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 1 ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '1' ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => true ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'true' ) ),
        );
    }

    /**
     * @dataProvider booleanFalseTestDataProvider
     */
    public function testCreateWithBooleanFalse(array $args)
    {
        $n = new Name;
        $ret = $n->create($args);
        $this->assertResultSuccess($ret);
        $n = Name::defaultRepo()->load($ret->key);
        $this->assertFalse($n->isConfirmed());
    }


    /**
     * @basedata false
     * @dataProvider booleanNullTestDataProvider
     */
    public function testCreateWithBooleanNull(array $args)
    {
        $n = new Name;
        $ret = $n->create($args);
        $this->assertResultSuccess($ret);

        $n = Name::defaultRepo()->load($ret->key);
        $this->assertNull($n->isConfirmed());

        $n = Name::load($n->id);
        $this->assertNull($n->isConfirmed());
        $this->successfulDelete($n);
    }


    /**
     * @basedata false
     * @dataProvider booleanTrueTestDataProvider
     */
    public function testCreateWithBooleanTrue(array $args)
    {
        $n = new Name;
        $ret = $n->create($args);
        $this->assertResultSuccess($ret);

        $n = Name::load($ret->key);
        $this->assertNotFalse($n);
        $this->assertTrue($n->isConfirmed(), 'Confirmed value should be TRUE.');

        $n = Name::load($n->id);
        $this->assertNotFalse($n);
        $this->assertTrue($n->isConfirmed(), 'Confirmed value should be TRUE.');
        $this->successfulDelete($n);
    }

    /**
     * @rebuild false
     */
    public function testModelClone()
    {
        $test1 = new Name;
        $test2 = clone $test1;
        $this->assertNotSame($test1, $test2);
    }


    /**
     * @basedata false
     */
    public function testModelColumnFilter()
    {
        $name = new Name;
        $ret = $name->create(array('name' => 'Foo' , 'country' => 'Taiwan' , 'address' => 'John'));
        $this->assertResultSuccess($ret);

        $name = Name::defaultRepo()->load($ret->key);
        is('XXXX' , $name->address , 'Should be canonicalized' );
    }

    public function testBooleanFromStringZero()
    {
        $n = new \TestApp\Model\Name;

        /** confirmed will be cast to true **/
        $ret = $n->create(array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '0' ));
        $this->assertResultSuccess( $ret );
        $n = Name::defaultRepo()->load($ret->key);

        $this->assertNotFalse($n);
        $this->assertNotNull($n->id);
        $this->assertFalse($n->isConfirmed());
        $this->successfulDelete($n);
    }


    /**
     * @rebuild false
     */
    public function testValueTypeConstraint()
    {
        // if it's a str type , we should not accept types not str.
        $n = new \TestApp\Model\Name;

        /**
         * name column is required, after type casting, it's NULL, so
         * create should fail.
         */
        $ret = $n->create(array( 'name' => false , 'country' => 'Type' ));
        $this->assertResultFail($ret);
        ok(! $n->id );
    }

    public function testModelColumnDefaultValueBuilder()
    {
        $name = new Name;
        $ret = $name->create(array(  'name' => 'Foo' , 'country' => 'Taiwan' ));
        $this->assertNotEmpty($ret->validations);
        $this->assertTrue(isset($ret->validations['address']));
        $this->assertTrue($ret->validations['address']['valid']);
        ok( $vlds = $ret->getSuccessValidations() );
        $this->assertCount(1, $vlds);

        $name = Name::defaultRepo()->load($ret->key);
        ok( $name->id );
        ok( $name->address );

        $ret = $name->create(array(  'name' => 'Foo', 'address' => 'fuck' , 'country' => 'Tokyo' ));
        ok( $ret->validations );

        foreach( $ret->getErrorValidations() as $vld ) {
            $this->assertFalse($vld['valid']);
            $this->assertEquals('Please don\'t',  $vld['message']);
        }
    }

    public function testLoadFromContstructor()
    {
        $name = new Name;
        $name = Name::createAndLoad(array( 
            'name' => 'John',
            'country' => 'Taiwan',
            'type' => 'type-a',
        ));
        $this->assertNotFalse($name);
        $this->assertNotNull($name->id);

        $name2 = Name::defaultRepo()->load($name->id);
        $this->assertEquals($name2->id , $name->id);
    }

    /**
     * @rebuild false
     */
    public function testValidValueBuilder()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array( 
            'name' => 'John',
            'country' => 'Taiwan',
            'type' => 'type-a',
        ));
        $this->assertResultSuccess($ret);

        $name = Name::defaultRepo()->load($ret->key);
        $this->assertEquals('Type Name A', $name->display('type'));

        $xml = $name->toXml();
        $dom = new DOMDocument;
        $dom->loadXml($xml);

        if (extension_loaded('yaml')) {
            $yaml = $name->toYaml();
            yaml_parse($yaml);
        }

        $json = $name->toJson();
        $this->assertNotEmpty($json);
        $this->assertNotEmpty(json_decode($json));
        $this->assertResultSuccess($name->delete());
    }

    /**
     * @rebuild false
     */
    public function testDeflator()
    {
        $n = new \TestApp\Model\Name;
        $n = Name::createAndLoad(array( 
            'name' => 'Deflator Test' , 
            'country' => 'Tokyo', 
            'confirmed' => '0',
            'date' => '2011-01-01'
        ));
        $this->assertNotFalse($n);

        $d = $n->getDate();
        $this->assertNotNull($d);
        $this->assertInstanceOf('DateTime', $d);
        $this->assertEquals('20110101' , $d->format( 'Ymd' ));

        $ret = $n->delete();
        $this->assertResultSuccess($ret);
    }

    /**
     * @dataProvider nameDataProvider
     * @rebuild false
     */
    public function testCreateWithDifferentNames($args)
    {
        $name = new Name;
        $name = Name::createAndLoad($args);

        $ret = $name->delete();
        $this->assertResultSuccess($ret);
    }

    /**
     * @dataProvider nameDataProvider
     * @rebuild false
     */
    public function testFromArray($args)
    {
        $instance = \TestApp\Model\Name::fromArray(array( 
            $args
        ));
        $this->assertInstanceOf( 'TestApp\Model\Name' ,  $instance);

        $collection = \TestApp\Model\NameCollection::fromArray(array( 
            $args,
            $args,
        ));
        $this->assertInstanceOf('TestApp\Model\NameCollection' , $collection);
    }

    /**
     * @rebuild false
     */
    public function testDateTimeInflator()
    {
        $n = new \TestApp\Model\Name;
        $date = new DateTime('2011-01-01 00:00:00');
        $n = Name::createAndLoad(array(
            'name' => 'Deflator Test',
            'country' => 'Tokyo',
            'confirmed' => false,
            'date' => $date,
        ));
        $this->assertNotFalse($n);

        $array = $n->toArray();
        $this->assertTrue(is_string( $array['date']));

        $d = $n->getDate(); // inflated
        $this->assertInstanceOf('DateTime' , $d);
        $this->assertEquals('20110101' , $d->format('Ymd'));
        $this->successfulDelete($n);
    }
}
