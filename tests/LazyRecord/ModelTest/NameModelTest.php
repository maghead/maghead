<?php
use LazyRecord\ModelTestCase;

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

    public function booleanFalseTestDataProvider()
    {
        return array(
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 0 ) ),
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '0' ) ),
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => false ) ),
#              array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'false' ) ),
            array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '' ) ),  // empty string should be (false)
            // array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'aa' ) ),
            // array( array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => 'bb' ) ),
        );
    }

    /**
     * @dataProvider booleanFalseTestDataProvider
     */
    public function testCreateWithBooleanFalse($args)
    {
        $n = new \TestApp\Model\Name;
        $ret = $n->create($args);
        ok( $ret->success , $ret  . " SQL: " . $ret->sql . print_r($ret->vars,1) );
        ok( $n->id );
        is( false, $n->confirmed );

        // reload
        ok( $n->load( $n->id )->success );
        is( false, $n->confirmed );
        ok( $n->delete()->success );
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
     * @dataProvider booleanTrueTestDataProvider
     */
    public function testCreateWithBooleanTrue($args)
    {
        $n = new \TestApp\Model\Name;
        $ret = $n->create($args);
        result_ok($ret);
        ok( $n->id );
        is( true, $n->confirmed, 'Confirmed value should be TRUE.' );
        // reload
        ok( $n->load( $n->id )->success );
        is( true, $n->confirmed , 'Confirmed value should be TRUE.' );
        ok( $n->delete()->success );
    }

    public function testModelClone()
    {
        $test1 = new \TestApp\Model\Name;
        $test2 = clone $test1;
        ok( $test1 !== $test2 );
    }

    public function testModelColumnFilter()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array('name' => 'Foo' , 'country' => 'Taiwan' , 'address' => 'John'));
        result_ok($ret);
        is('XXXX' , $name->address , 'Should be canonicalized' );
    }

    public function testBooleanFromStringZero()
    {
        $n = new \TestApp\Model\Name;

        /** confirmed will be cast to true **/
        $ret = $n->create(array( 'name' => 'Foo' , 'country' => 'Tokyo', 'confirmed' => '0' ));
        result_ok( $ret );
        ok( $n->id );
        is( false, $n->confirmed );
        ok( $n->delete()->success );
    }

    public function testValueTypeConstraint()
    {
        // if it's a str type , we should not accept types not str.
        $n = new \TestApp\Model\Name;
        /**
         * name column is required, after type casting, it's NULL, so
         * create should fail.
         */
        $ret = $n->create(array( 'name' => false , 'country' => 'Tokyo' ));
        ok( ! $ret->success );
        ok( ! $n->id );
    }

    public function testModelColumnDefaultValueBuilder()
    {
        $name = new \TestApp\Model\Name;
        $ret = $name->create(array(  'name' => 'Foo' , 'country' => 'Taiwan' ));

        result_ok( $ret );
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
        $name->create(array( 
            'name' => 'John',
            'country' => 'Taiwan',
            'type' => 'type-a',
        ));
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
        ok( $ret->success );
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
        ok( $ret->success );
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
        ok( $ret->success , $ret );

        $array = $n->toArray();
        ok( is_string( $array['date'] ) );

        $d = $n->date; // inflated
        isa_ok( 'DateTime' , $d );
        is( '20110101' , $d->format( 'Ymd' ) );
        ok( $n->delete()->success );
    }


    /*
    public function testCreateSpeed()
    {
        // FIXME: On build machine,  we got 21185.088157654, that's really slow, fix later.
        return;

        $s = microtime(true);
        $n = new \TestApp\Model\Name;
        $ids = array();
        $cnt = 10;
        foreach( range(1,$cnt) as $i ) {
            // you can use _create to gain 120ms faster
            $ret = $n->create(array(
                'name' => "Deflator Test $i", 
                'country' => 'Tokyo', 
                'confirmed' => true,
                'date' => new DateTime('2011-01-01 00:00:00'),
            ));
            $ids[] = $n->id;
        }

        $duration = (microtime(true) - $s) / $cnt * 1000000; // get average microtime.

        // $limit = 1400; before commit: e9c891ee3640f58871eb676df5f8f54756b14354
        $limit = 3500;
        if( $duration > $limit ) {
            ok( false , "performance test: should be less than $limit ms, got $duration ms." );
        }

        foreach( $ids as $id ) {
            \TestApp\Model\Name::delete($id);
        }
    }
     */
}
