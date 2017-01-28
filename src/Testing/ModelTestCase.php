<?php

namespace Maghead\Testing;

use Maghead\Utils\ClassUtils;
use Maghead\SeedBuilder;
use Maghead\SqlBuilder\SqlBuilder;
use Maghead\TableParser\TableParser;
use Maghead\Schema\SchemaGenerator;
use Maghead\Schema\SchemaCollection;
use Maghead\Schema\SchemaUtils;
use Maghead\Manager\TableManager;

abstract class ModelTestCase extends BaseTestCase
{
    public $schemaHasBeenBuilt = false;

    public $schemaClasses = array();

    protected $allowConnectionFailure = false;

    protected $sqlBuilder;

    protected $tableManager;

    public function setUp()
    {
        if ($this->onlyDriver !== null && $this->getDataSource() != $this->onlyDriver) {
            return $this->markTestSkipped("{$this->onlyDriver} only");
        }

        $this->prepareConnection();

        // Ensure that we use the correct default data source ID
        $this->assertEquals($this->getDataSource(), $this->config->getDefaultDataSourceId());
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

        if (false === $this->schemaHasBeenBuilt) {
            $g = new SchemaGenerator($this->config);
            $g->setForceUpdate(true);
            $g->generate($schemas);
            $this->schemaHasBeenBuilt = true;
        }

        if ($rebuild === false) {
            $tableParser = TableParser::create($this->conn, $this->queryDriver, $this->config);
            $tables = $tableParser->getTables();
            $schemas = array_filter($schemas, function ($schema) use ($tables) {
                return !in_array($schema->getTable(), $tables);
            });
        }

        $this->sqlBuilder = SqlBuilder::create($this->queryDriver, ['rebuild' => $rebuild]);

        $this->tableManager = new TableManager($this->conn, $this->sqlBuilder, $this->logger);
        $this->tableManager->build($schemas);

        if ($rebuild && $basedata) {
            $seeder = new SeedBuilder($this->logger);
            $seeder->build(new SchemaCollection($schemas));
            $seeder->buildConfigSeeds($this->config);
        }
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
