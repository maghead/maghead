<?php

namespace LazyRecord\Testing;

use LazyRecord\ConnectionManager;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
use LazyRecord\SeedBuilder;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\TableParser\TableParser;
use PDOException;

abstract class ModelTestCase extends BaseTestCase
{
    public $schemaHasBeenBuilt = false;

    public $schemaClasses = array();

    protected $allowConnectionFailure = false;

    protected $conn;

    protected $queryDriver;

    public function setUp()
    {
        if ($this->onlyDriver !== null && $this->getDriverType() != $this->onlyDriver) {
            return $this->markTestSkipped("{$this->onlyDriver} only");
        }

        if ($this->conn == null) {
            $configLoader = ConfigLoader::getInstance();
            $configLoader->loadFromSymbol(true);
            $configLoader->setDefaultDataSourceId($this->getDriverType());
            $connManager = ConnectionManager::getInstance();
            $connManager->free();
            $connManager->init($configLoader);

            try {
                $this->conn = $connManager->getConnection($this->getDriverType());
            } catch (PDOException $e) {
                if ($this->allowConnectionFailure) {
                    $this->markTestSkipped(
                        sprintf("Can not connect to database by data source '%s' message:'%s' config:'%s'",
                            $this->getDriverType(),
                            $e->getMessage(),
                            var_export($configLoader->getDataSource($this->getDriverType()), true)
                        ));

                    return;
                }
                echo sprintf("Can not connect to database by data source '%s' message:'%s' config:'%s'",
                    $this->getDriverType(),
                    $e->getMessage(),
                    var_export($configLoader->getDataSource($this->getDriverType()), true)
                );
                throw $e;
            }

            $this->queryDriver = $connManager->getQueryDriver($this->getDriverType());
            $this->assertInstanceOf('SQLBuilder\\Driver\\BaseDriver', $this->queryDriver, 'QueryDriver object OK');
        }

        // Rebuild means rebuild the database for new tests
        $annnotations = $this->getAnnotations();
        $rebuild = true;
        $basedata = true;
        if (isset($annnotations['method']['rebuild'][0]) && $annnotations['method']['rebuild'][0] == 'false') {
            $rebuild = false;
        }
        if (isset($annnotations['method']['basedata'][0]) && $annnotations['method']['basedata'][0] == 'false') {
            $basedata = false;
        }

        $schemas = ClassUtils::schema_classes_to_objects($this->getModels());
        $this->buildSchemaTables($schemas, $rebuild);
        if ($basedata) {
            $runner = new SeedBuilder($this->config, $this->logger);
            foreach ($schemas as $schema) {
                $runner->buildSchemaSeeds($schema);
            }
            if ($scripts = $this->config->getSeedScripts()) {
                foreach ($scripts as $script) {
                    $runner->buildScriptSeed($script);
                }
            }
        }
    }

    protected function buildSchemaTables(array $schemas, $rebuild = true)
    {
        $parser = TableParser::create($this->conn, $this->queryDriver);
        $tables = $parser->getTables();

        $builder = SqlBuilder::create($this->queryDriver, array('rebuild' => $rebuild));
        if ($sqls = $builder->prepare()) {
            foreach ($sqls as $sql) {
                $this->conn->query($sql);
            }
        }
        foreach ($schemas as $schema) {
            // Skip schema building if table already exists.
            if ($rebuild === false && in_array($schema->getTable(), $tables)) {
                // continue;
            }
            $sqls = $builder->build($schema);
            $this->assertNotEmpty($sqls);
            foreach ($sqls as $sql) {
                $this->conn->query($sql);
            }
        }
        if ($sqls = $builder->finalize()) {
            foreach ($sqls as $sql) {
                $this->conn->query($sql);
            }
        }
    }



    public function tearDown()
    {
        $this->conn = null;
    }

    /**
     * Test cases.
     */
    public function testClasses()
    {
        foreach ($this->getModels() as $class) {
            class_ok($class);
        }
    }
}
