<?php
use LazyRecord\QueryDriver;
use LazyRecord\ConnectionManager;
use LazyRecord\SqlBuilder;
use LazyRecord\ConfigLoader;
use LazyRecord\Command\CommandUtils;

abstract class PHPUnit_Framework_ModelTestCase extends PHPUnit_Framework_TestCase
{
    public $driver = 'sqlite';

    public $schemaHasBeenBuilt = false;

    public $schemaPath = 'tests/schema';

    public $inIsolation = false;

    public $schemaClasses = array( );

    public function getDSN()
    {
        if( $dsn = getenv('DB_' . strtoupper($this->driver) .  '_DSN') )
            return $dsn;
    }

    public function getDatabaseName() 
    {
        if( $name = getenv('DB_' . strtoupper($this->driver) .  '_NAME') )
            return $name;
    }

    public function getDatabaseUser() 
    {
        if( $user = getenv('DB_' . strtoupper($this->driver) . '_USER') )
            return $user;
    }

    public function getDatabasePassword() 
    {
        if( $pass = getenv('DB_' . strtoupper($this->driver) . '_PASS') )
            return $pass;
    }

    public function setUp()
    {
        if( ! extension_loaded('pdo') ) {
            $this->markTestSkipped('pdo extension is required for model testing');
            return;
        }

        // free and override default connection
        ConnectionManager::getInstance()->free();
        QueryDriver::free();

        $config = ConfigLoader::getInstance();
        $config->loaded = true;
        $config->config = array( 'schema' => array( 'auto_id' => true ) );

        if( $dsn = $this->getDSN() ) {
            $config = array('dsn' => $dsn);
            $user = $this->getDatabaseUser();
            $pass = $this->getDatabasePassword();
            if($user) $config['user'] = $user;
            if($pass) $config['pass'] = $pass;
            ConnectionManager::getInstance()->addDataSource('default', $config);
        }
        elseif( $this->getDatabaseName() ) {
            ConnectionManager::getInstance()->addDataSource('default', array( 
                'driver' => $this->driver,
                'database'  => $this->getDatabaseName(),
                'user' => $this->getDatabaseUser(),
                'pass' => $this->getDatabasePassword(),
            ));
        } else {
            $this->markTestSkipped("{$this->driver} database configuration is required.");
        }

        try {
            $dbh = ConnectionManager::getInstance()->getConnection('default');
        } catch ( PDOException $e ) {
            $this->markTestSkipped('Can not connect to database, test skipped: ' . $e->getMessage() );
            return;
        }

        $driver = ConnectionManager::getInstance()->getQueryDriver('default');
        ok( $driver );

        $builder = LazyRecord\SqlBuilder\SqlBuilder::create( $driver , array( 'rebuild' => true ));
        ok( $builder );


        /* this will generate schema files */
        // sqlite :memory: require this */
        /*
        $finder = new LazyRecord\Schema\SchemaFinder;
        $finder->addPath( 'tests/schema' );
        $finder->find();
        */

        $generator = new \LazyRecord\Schema\SchemaGenerator;
        $generator->setLogger( $this->getLogger() );
        $schemas = CommandUtils::schema_classes_to_objects( $this->getModels() );
        $classMap = $generator->generate( $schemas );
        ok( $classMap );
        foreach( $schemas as $schema ) {
            $sqls = $builder->build($schema);
            ok( $sqls );
            foreach( $sqls as $sql ) {
                $dbh->query( $sql );
            }
        }
        CommandUtils::build_basedata( $schemas );
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



