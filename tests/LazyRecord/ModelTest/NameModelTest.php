<?php
use LazyRecord\Testing\ModelTestCase;

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
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => ' ' ) ),  // space string (true)
        );
    }

    /**
     * @dataProvider booleanFalseTestDataProvider
     */
    public function testCreateWithBooleanFalse(array $args)
    {
        $n = new \TestApp\Model\Name;
        $ret = $n->create($args);
        $this->assertResultSuccess($ret);
        $this->assertFalse($n->confirmed);
    }


    /**
     * @basedata false
     * @dataProvider booleanNullTestDataProvider
     */
    public function testCreateWithBooleanNull(array $args)
    {
        $n = new \TestApp\Model\Name;
        $ret = $n->create($args);
        $this->assertResultSuccess($ret);

        ok($n->id);
        $this->assertNull($n->confirmed);

        $ret = $n->load($n->id);
        $this->assertResultSuccess($ret);
        $this->assertNull($n->confirmed);
        $this->successfulDelete($n);
    }


    /**
     * @basedata false
     * @dataProvider booleanTrueTestDataProvider
     */
    public function testCreateWithBooleanTrue(array $args)
    {
        $n = new \TestApp\Model\Name;
        $ret = $n->create($args);
        $this->assertResultSuccess($ret);

        ok($n->id);

        $this->assertTrue($n->confirmed, 'Confirmed value should be TRUE.');

        $this->assertResultSuccess($n->load($n->id));

        $this->assertTrue($n->confirmed, 'Confirmed value should be TRUE.');

        $this->successfulDelete($n);
    }

    /**
     * @rebuild false
     */
    public function testModelClone()
    {
        $test1 = new \TestApp\Model\Name;
        $test2 = clone $test1;
        $this->assertNotSame($test1, $test2);
    }


    /**
     * @basedata false
     */
    public function testModelColumnFilter()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array('name' => 'Foo' , 'country' => 'Taiwan' , 'address' => 'John'));
        $this->assertResultSuccess($ret);
        is('XXXX' , $name->address , 'Should be canonicalized' );
    }

    public function testBooleanFromStringZero()
    {
        $n = new \TestApp\Model\Name;

        /** confirmed will be cast to true **/
        $ret = $n->create(array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '0' ));
        $this->assertResultSuccess( $ret );
        ok( $n->id );

        $this->assertFalse($n->confirmed);
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
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array(  'name' => 'Foo' , 'country' => 'Taiwan' ));

        $this->assertResultSuccess($ret);

        ok( $ret->validations );

        ok( $ret->validations['address'] );
        ok( $ret->validations['address']->valid );

        ok( $vlds = $ret->getSuccessValidations() );
        count_ok( 1, $vlds );

        ok( $name->id );
        ok( $name->address );

        $ret = $name->create(array(  'name' => 'Foo', 'address' => 'fuck' , 'country' => 'Tokyo' ));
        ok( $ret->validations );

        foreach( $ret->getErrorValidations() as $vld ) {
            is( false , $vld->valid );
            is( 'Please don\'t',  $vld->message );
        }
    }

    public function testLoadFromContstructor()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array( 
            'name' => 'John',
            'country' => 'Taiwan',
            'type' => 'type-a',
        ));
        $this->assertResultSuccess($ret);
        ok( $name->id );

        $name2 = new \TestApp\Model\Name( $name->id );
        is( $name2->id , $name->id );
    }

    public function testValidValueBuilder()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array( 
            'name' => 'John',
            'country' => 'Taiwan',
            'type' => 'type-a',
        ));
        $this->assertResultSuccess($ret);
        is( 'Type Name A', $name->display( 'type' ) );

        $xml = $name->toXml();
        ok( $xml );

        $dom = new DOMDocument;
        $dom->loadXml( $xml );


        if (extension_loaded('yaml')) {
            $yaml = $name->toYaml();
            ok( $yaml );
            yaml_parse($yaml);
        }

        $json = $name->toJson();
        ok( $json );
        json_decode( $json );

        ok( $name->delete()->success );
    }

    public function testDeflator()
    {
        $n = new \TestApp\Model\Name;
        $ret = $n->create(array( 
            'name' => 'Deflator Test' , 
            'country' => 'Tokyo', 
            'confirmed' => '0',
            'date' => '2011-01-01'
        ));
        $this->assertResultSuccess($ret);

        $d = $n->date;
        ok( $d );
        isa_ok( 'DateTime' , $d );
        is( '20110101' , $d->format( 'Ymd' ) );
        ok( $n->delete()->success );
    }

    /**
     * @dataProvider nameDataProvider
     */
    public function testCreateWithName($args)
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create($args);
        $this->assertResultSuccess($ret);

        $ret = $name->delete();
        ok( $ret->success );
    }

    /**
     * @dataProvider nameDataProvider
     */
    public function testFromArray($args)
    {
        $instance = \TestApp\Model\Name::fromArray(array( 
            $args
        ));
        ok( $instance );
        isa_ok( 'TestApp\Model\Name' ,  $instance );

        $collection = \TestApp\Model\NameCollection::fromArray(array( 
            $args,
            $args,
        ));
        isa_ok( 'TestApp\Model\NameCollection' , $collection );
    }

    public function testDateTimeInflator()
    {
        $n = new \TestApp\Model\Name;
        $date = new DateTime('2011-01-01 00:00:00');
        $ret = $n->create(array( 
            'name' => 'Deflator Test' , 
            'country' => 'Tokyo', 
            'confirmed' => false,
            'date' => $date,
        ));
        $this->assertResultSuccess($ret);

        $array = $n->toArray();
        ok( is_string( $array['date'] ) );

        $d = $n->date; // inflated
        isa_ok( 'DateTime' , $d );
        is( '20110101' , $d->format( 'Ymd' ) );

        $this->successfulDelete($n);
    }
}
