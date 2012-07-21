<?php
use LazyRecord\QueryDriver;
use LazyRecord\ConnectionManager;
use LazyRecord\SqlBuilder;
use LazyRecord\ConfigLoader;

abstract class PHPUnit_Framework_ModelTestCase extends PHPUnit_Framework_TestCase
{
    public $dsn;

    public $schemaPath = 'tests/schema';

    public $schemaClasses = array( );


    public function setUp()
    {
        // free and override default connection
        ConnectionManager::getInstance()->free();
        QueryDriver::free();


        if( $dsn = getenv('DB_DSN') ) {
            $this->dsn = $dsn;
            $config = array(
                'dsn'  => $this->dsn,
            );
            if( $user = getenv('DB_USER') )
                $config['user'] = $user;
            if( $pass = getenv('DB_PASS') )
                $config['pass'] = $pass;
            ConnectionManager::getInstance()->addDataSource('default', $config);
        } elseif( $this->dsn ) {
            ConnectionManager::getInstance()->addDataSource('default', array( 
                'dsn'  => $this->dsn,
                'user' => 'testing',
                'pass' => 'testing',
            ));
        } else {
            // a little patch for config (we need auto_id for testing)
            $config = ConfigLoader::getInstance();
            $config->unload();
            $config->load();
            $config->initForBuild();
        }

        // $config->loaded = true;
        // $config->config = array( 'schema' => array( 'auto_id' => true ) );


        $dbh = ConnectionManager::getInstance()->getConnection('default');
        $driver = ConnectionManager::getInstance()->getQueryDriver('default');
        $builder = LazyRecord\SqlBuilder\SqlBuilderFactory::create( $driver , array(
            'rebuild' => true,
        ));
		ok( $builder );

        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->addPath( 'tests/schema/' );
        $finder->loadFiles();

		$generator = new \LazyRecord\Schema\SchemaGenerator;
		$generator->setLogger( $this->getLogger() );
		$classMap = $generator->generate( $finder->getSchemaClasses() );
        ok( $classMap );

        $schemaClasses = $this->getModels();
        foreach( $schemaClasses as $class ) {
            $schema = new $class;
            $sqls = $builder->build($schema);
            ok( $sqls );
            foreach( $sqls as $sql ) {
                $dbh->query( $sql );
            }
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

    public function resultOK($expect,$ret)
    {
        ok( $ret );
        if( $ret->success == $expect ) {
            ok( $ret->success , $ret->message );
        }
        else {
            var_dump( $ret->sql ); 
            echo $ret->exception;
            ok( $ret->success );
        }
    }
}



