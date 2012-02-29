<?php
require_once 'tests/schema/tests/AuthorBooks.php';
use Lazy\SchemaSqlBuilder;

class PHPUnit_ModelTestCase extends PHPUnit_Framework_TestCase
{

    public $driverType = 'sqlite';
    public $dsn = 'sqlite::memory:';
    public $schemaPath = 'tests/schema';
    public $schemaClasses = array( );

    public function setup()
    {
        Lazy\QueryDriver::free();
        Lazy\ConnectionManager::getInstance()->free();
        Lazy\ConnectionManager::getInstance()->addDataSource('default', array( 'dsn' => $this->dsn ));

        $dbh = Lazy\ConnectionManager::getInstance()->getConnection();

        // initialize schema files
        $builder = new SchemaSqlBuilder( $this->driverType , Lazy\ConnectionManager::getInstance()->getQueryDriver('default'));
		ok( $builder );

		$generator = new \Lazy\Schema\SchemaGenerator;
		$generator->addPath( $this->schemaPath );
		$generator->setLogger( $this->getLogger() );
		$classMap = $generator->generate();
        ok( $classMap );

        $schemaClasses = $this->getModels();
        foreach( $schemaClasses as $class ) {
            $schema = new $class;
            $sqls = $builder->build($schema);
            ok( $sqls );
            foreach( $sqls as $sql )
                $dbh->query( $sql );
        }
    }


	public function getLogger()
	{
		return new TestLogger;
	}
}

class ModelTest extends PHPUnit_ModelTestCase
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

    public function testClass()
    {
        class_ok( '\tests\Author' );
    }

    /****************************
     * Basic CRUD Test 
     ***************************/
	public function testModel()
	{
        $author = new \tests\Author;
        ok( $author->_schema );

        $a2 = new \tests\Author;
        $ret = $a2->load( array( 'name' => 'A record does not exist.' ) );
        ok( ! $ret->success );
        ok( ! $a2->id );

        $a2->loadOrCreate( array( 'name' => 'Record1' , 'email' => 'record@record.org' , 'identity' => 'record' ) , 'name' );
        ok( $id = $a2->id );

        $a2->loadOrCreate( array( 'name' => 'Record1' , 'email' => 'record@record.org' , 'identity' => 'record' ) , 'name' );
        is( $id, $a2->id );

        $ret = $a2->create(array( 'name' => 'long string \'` long string' , 'email' => 'email' , 'identity' => 'id' ));
        ok( $ret->success );
        ok( $a2->id );

        $ret = $a2->create(array( 'xxx' => true, 'name' => 'long string \'` long string' , 'email' => 'email2' , 'identity' => 'id2' ));
        ok( $ret->success );
        ok( $a2->id );



        $ret = $author->create(array());
        ok( $ret );
        ok( ! $ret->success );
        ok( $ret->message );
        is( 'Empty arguments' , $ret->message );

        $query = $author->createQuery();
        ok( $query );

        $ret = $author->create(array( 'name' => 'Foo' , 'email' => 'foo@google.com' , 'identity' => 'foo' ));
        ok( $ret );
        ok( $id = $ret->id );
        ok( $ret->success );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );

        $ret = $author->load( $id );
        ok( $ret->success );
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->load(array( 'name' => 'Foo' ));
        ok( $ret->success );
        is( $id , $author->id );
        is( 'Foo', $author->name );
        is( 'foo@google.com', $author->email );
        is( false , $author->confirmed );

        $ret = $author->update(array( 'name' => 'Bar' ));
        ok( $ret->success );

        is( 'Bar', $author->name );

        $ret = $author->delete();
        ok( $ret->success );

        $data = $author->toArray();
        ok( $data );
        ok( !empty($data));
    }


    public function testDefaultBuilder()
    {


    }

    public function testStaticFunctions() 
    {
        $record = \tests\Author::create(array( 
            'name' => 'Mary',
            'email' => 'zz@zz',
            'identity' => 'zz',
        ));
        ok( $record->_result->success );

        $record = \tests\Author::load( (int) $record->_result->id );
        ok( $record );
        ok( $id = $record->id );

        $record = \tests\Author::load( array( 'id' => $id ));
        ok( $record );
        ok( $record->id );


        /**
         * Which runs:
         *    UPDATE authors SET name = 'Rename' WHERE name = 'Mary'
         */
        $ret = \tests\Author::update(array( 'name' => 'Rename' ))
            ->where()
                ->equal('name','Mary')
                ->back()
                ->execute();
        ok( $ret->success );

        $ret = \tests\Author::delete()
            ->where()
                ->equal('name','Rename')
            ->back()->execute();
        ok( $ret->success );
	}
}

