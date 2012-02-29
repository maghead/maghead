<?php
use Lazy\QueryDriver;
use Lazy\ConnectionManager;
use Lazy\SchemaSqlBuilder;

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

        $dbh = ConnectionManager::getInstance()->getConnection();

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



