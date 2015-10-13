<?php
namespace LazyRecord\Testing;
use LazyRecord\ConnectionManager;
use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ClassUtils;
use LazyRecord\SeedBuilder;
use LazyRecord\Result;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\Testing\BaseTestCase;
use PHPUnit_Framework_TestCase;
use PDOException;

abstract class ModelTestCase extends BaseTestCase
{
    public $schemaHasBeenBuilt = false;

    public $schemaClasses = array( );

    protected $allowConnectionFailure = false;


    public function setUp()
    {
        $annnotations = $this->getAnnotations();

        $configLoader = ConfigLoader::getInstance();
        $configLoader->loadFromSymbol(true);
        $configLoader->setDefaultDataSourceId($this->getDriverType());

        $connManager = ConnectionManager::getInstance();
        $connManager->init($configLoader);

        try {
            $dbh = $connManager->getConnection($this->getDriverType());
        } catch (PDOException $e) {
            if ($this->allowConnectionFailure) {
                $this->markTestSkipped(
                    sprintf("Can not connect to database by data source '%s' message:'%s' config:'%s'", 
                        $this->getDriverType(),
                        $e->getMessage(),
                        var_export($configLoader->getDataSource($this->getDriverType()), true)
                    ));
                return;
            } else {
                echo sprintf("Can not connect to database by data source '%s' message:'%s' config:'%s'", 
                    $this->getDriverType(),
                    $e->getMessage(),
                    var_export($configLoader->getDataSource($this->getDriverType()), true)
                );
                throw $e;
            }
        }

        $driver = $connManager->getQueryDriver($this->getDriverType());
        $this->assertInstanceOf('SQLBuilder\\Driver\\BaseDriver', $driver,'QueryDriver object OK');

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
            $this->assertNotNull($builder);

            // $schemas = ClassUtils::schema_classes_to_objects($this->getModels());
            $schemas = ClassUtils::schema_classes_to_objects($this->getModels());
            foreach( $schemas as $schema ) {
                $sqls = $builder->build($schema);
                $this->assertNotEmpty($sqls);

                foreach ($sqls as $sql) {
                    $dbh->query( $sql );
                }
            }
            if ($basedata) {
                $runner = new SeedBuilder($this->config, $this->logger);
                foreach($schemas as $schema) {
                    $runner->buildSchemaSeeds($schema);
                }
                if ($scripts = $this->config->getSeedScripts()) {
                    foreach($scripts as $script) {
                        $runner->buildScriptSeed($script);
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



