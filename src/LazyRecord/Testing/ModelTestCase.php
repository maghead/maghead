<?php
namespace LazyRecord\Testing;
use LazyRecord\ConnectionManager;
use LazyRecord\ConfigLoader;
use LazyRecord\Command\CommandUtils;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ClassUtils;
use LazyRecord\SeedRunner;
use LazyRecord\Result;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\Testing\BaseTestCase;
use PHPUnit_Framework_TestCase;

abstract class ModelTestCase extends BaseTestCase
{
    public $schemaHasBeenBuilt = false;

    public $schemaClasses = array( );

    public function setUp()
    {
        $annnotations = $this->getAnnotations();
        if ($dsn = $this->getDSN()) {
            $config = array('dsn' => $dsn);
            $user = $this->getDatabaseUser();
            $pass = $this->getDatabasePassword();
            $config['user'] = $user;
            $config['pass'] = $pass;
            ConnectionManager::getInstance()->addDataSource('default', $config);
        }
        elseif ( $this->getDatabaseName() ) {
            ConnectionManager::getInstance()->addDataSource('default', array( 
                'driver' => $this->driver,
                'database'  => $this->getDatabaseName(),
                'user' => $this->getDatabaseUser(),
                'pass' => $this->getDatabasePassword(),
            ));
        } else {
            $this->markTestSkipped("{$this->driver} database configuration is missing.");
        }

        try {
            $dbh = ConnectionManager::getInstance()->getConnection('default');
        } catch (PDOException $e) {
            $this->markTestSkipped('Can not connect to database, test skipped: ' . $e->getMessage() );
            return;
        }

        $driver = ConnectionManager::getInstance()->getQueryDriver('default');
        ok($driver,'QueryDriver object OK');


        // Rebuild means rebuild the database for new tests
        $rebuild = true;
        $basedata = true;
        if (isset($annnotations['method']['rebuild'][0]) && $annnotations['method']['rebuild'][0] == 'false') {
            $rebuild = false;
        }
        if (isset($annnotations['method']['basedata'][0]) && $annnotations['method']['basedata'][0] == 'false') {
            $basedata = false;
        }

        if ($rebuild) {
            $builder = SqlBuilder::create($driver , array('rebuild' => true));
            ok($builder, 'SqlBuilder OK');

            $schemas = ClassUtils::schema_classes_to_objects( $this->getModels() );
            foreach( $schemas as $schema ) {
                $sqls = $builder->build($schema);
                ok($sqls);
                foreach( $sqls as $sql ) {
                    $dbh->query( $sql );
                }
            }
            if ($basedata) {
                $runner = new SeedRunner($this->config, $this->logger);
                foreach($schemas as $schema) {
                    $runner->runSchemaSeeds($schema);
                }
                if ($scripts = $this->config->getSeedScripts()) {
                    foreach($scripts as $script) {
                        $runner->runSeedScript($script);
                    }
                }
            }
        }
    }

    /**
     * Test cases
     */
    public function testClasses()
    {
        foreach ($this->getModels() as $class ) {
            class_ok($class);
        }
    }

}



