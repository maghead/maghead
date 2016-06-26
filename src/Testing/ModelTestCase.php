<?php

namespace LazyRecord\Testing;

use LazyRecord\ConnectionManager;
use LazyRecord\Connection;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
use LazyRecord\SeedBuilder;
use LazyRecord\SqlBuilder\SqlBuilder;
use LazyRecord\TableParser\TableParser;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Schema\SchemaCollection;
use LazyRecord\Bootstrap;
use PDOException;

abstract class ModelTestCase extends BaseTestCase
{
    public $schemaHasBeenBuilt = false;

    public $schemaClasses = array();

    protected $allowConnectionFailure = false;

    protected $sqlBuilder;

    public function setUp()
    {
        if ($this->onlyDriver !== null && $this->getDriverType() != $this->onlyDriver) {
            return $this->markTestSkipped("{$this->onlyDriver} only");
        }

        $this->prepareConnection();


        // Ensure that we use the correct default data source ID
        $this->assertEquals($this->getDriverType(), $this->config->getDefaultDataSourceId());
        $this->assertInstanceOf('SQLBuilder\\Driver\\BaseDriver', $this->queryDriver, 'QueryDriver object OK');


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

        if (false === $this->schemaHasBeenBuilt) {
            $g = new SchemaGenerator($this->config);
            $g->setForceUpdate(true);
            $g->generate($schemas);
            $this->schemaHasBeenBuilt = true;
        }

        if ($rebuild === false) {
            $tableParser = TableParser::create($this->conn, $this->queryDriver, $this->config);
            $tables = $tableParser->getTables();
            $schemas = array_filter($schemas, function($schema) use ($tables) {
                return !in_array($schema->getTable(), $tables);
            });
        }

        $this->sqlBuilder = SqlBuilder::create($this->queryDriver, array('rebuild' => $rebuild));
        $this->buildSchemaTables($schemas, $rebuild);

        if ($rebuild && $basedata) {
            $seeder = new SeedBuilder($this->logger);
            $seeder->build(new SchemaCollection($schemas));
            $seeder->buildConfigSeeds($this->config);
        }
    }

    protected function dropSchemaTables($schemas)
    {
        $bootstrap = new Bootstrap($this->conn, $this->sqlBuilder, $this->logger);
        $bootstrap->remove($schemas);
    }

    protected function buildSchemaTables(array $schemas, $rebuild = true)
    {
        $bootstrap = new Bootstrap($this->conn, $this->sqlBuilder, $this->logger);
        $bootstrap->build($schemas);
    }

    public function testClasses()
    {
        foreach ($this->getModels() as $class) {
            class_ok($class);
        }
    }
}
