<?php

namespace Maghead\Testing;

use Maghead\ConfigLoader;
use Maghead\Bootstrap;
use Maghead\Manager\ConnectionManager;
use Maghead\Utils\ClassUtils;
use Maghead\SeedBuilder;
use Maghead\TableBuilder\TableBuilder;
use Maghead\TableParser\TableParser;
use Maghead\Generator\Schema\SchemaGenerator;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\SchemaUtils;
use Maghead\Manager\TableManager;

abstract class ModelTestCase extends BaseTestCase
{
    /**
     * Define this to support multiple connection
     */
    protected $requiredDataSources = [];

    protected $schemaHasBeenBuilt = false;

    protected $schemaClasses = [];

    protected $tableManager;

    public function setUp()
    {
        if ($this->onlyDriver !== null && $this->getDefaultDataSourceId() != $this->onlyDriver) {
            return $this->markTestSkipped("{$this->onlyDriver} only");
        }

        parent::setUp();

        // Ensure that we use the correct default data source ID
        $this->assertEquals($this->getDefaultDataSourceId(), $this->config->getDefaultDataSourceId());
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

        $schemas = SchemaUtils::instantiateSchemaClasses($this->getModels());
        if (! $this->schemaHasBeenBuilt) {
            $this->prepareSchemaFiles($schemas);
        }

        // TODO: get connection from user specific properties
        // 1. with default connection only.
        // 2. with multiple connections
        $this->prepareTables($this->conn, $this->queryDriver, $schemas, $rebuild);

        if ($rebuild && $basedata) {
            $this->prepareBaseData($schemas);
        }
    }

    protected function prepareBaseData($schemas)
    {
        $seeder = new SeedBuilder($this->logger);
        $seeder->build(new SchemaCollection($schemas));
        $seeder->buildConfigSeeds($this->config);
    }

    protected function prepareTables($conn, $queryDriver, array $schemas, bool $rebuild)
    {
        if ($rebuild === false) {
            $tableParser = TableParser::create($conn, $queryDriver, $this->config);
            $tables = $tableParser->getTables();
            $schemas = array_filter($schemas, function ($schema) use ($tables) {
                return !in_array($schema->getTable(), $tables);
            });
        }

        // Build table from schema
        $sqlBuilder = TableBuilder::create($queryDriver, ['rebuild' => $rebuild]);
        $this->tableManager = new TableManager($conn, $sqlBuilder, $this->logger);
        $this->tableManager->build($schemas);
    }

    protected function prepareSchemaFiles(array $schemas)
    {
        $g = new SchemaGenerator($this->config);
        $g->setForceUpdate(true);
        $g->generate($schemas);
        $this->schemaHasBeenBuilt = true;
    }

    protected function dropSchemaTables($schemas)
    {
        $this->tableManager->remove($schemas);
    }

    protected function buildSchemaTables(array $schemas)
    {
        $this->tableManager->build($schemas);
    }

    public function testClasses()
    {
        foreach ($this->getModels() as $class) {
            class_ok($class);
        }
    }
}
