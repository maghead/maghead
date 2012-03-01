<?php
use Lazy\QueryDriver;
use Lazy\ConnectionManager;
use Lazy\SchemaSqlBuilder;
use Lazy\ConfigLoader;

abstract class PHPUnit_Framework_ModelTestCase extends PHPUnit_Framework_TestCase
{

    public $driverType = 'sqlite';

    public $dsn = 'sqlite::memory:';

    public $schemaPath = 'tests/schema';

    public $schemaClasses = array( );



    public function setup()
    {
        QueryDriver::free();
        ConnectionManager::getInstance()->free();
        ConnectionManager::getInstance()->addDataSource('default', array( 'dsn' => $this->dsn ));


        // a little patch for config (we need auto_id for testing)
        $config = ConfigLoader::getInstance();
        $config->unload();
        $config->loaded = true;
        $config->config = array( 'schema' => array( 'auto_id' => true ) );


        $dbh = ConnectionManager::getInstance()->getConnection();

        // initialize schema files
        $builder = new SchemaSqlBuilder( $this->driverType , Lazy\ConnectionManager::getInstance()->getQueryDriver('default'));
		ok( $builder );

        $finder = new Lazy\Schema\SchemaFinder;
        $finder->addPath( 'tests/schema/' );
        $finder->loadFiles();

		$generator = new \Lazy\Schema\SchemaGenerator;
		$generator->setLogger( $this->getLogger() );
		$classMap = $generator->generate( $finder->getSchemaClasses() );
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


    public function testClass()
    {
        foreach( $this->getModels() as $class ) 
            class_ok( $class );
    }

}



