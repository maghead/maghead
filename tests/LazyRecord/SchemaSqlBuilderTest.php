<?php
use LazyRecord\Sqlbuilder\SqlBuilder;
use LazyRecord\Connection;
use LazyRecord\Testing\BaseTestCase;

class SqlBuilderTest extends BaseTestCase
{

    public function schemaProvider()
    {
        return $this->matrixDataProvider([ 'mysql', 'sqlite', 'pgsql' ], [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AddressSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\BookSchema,
            new \TestApp\Model\NameSchema,
        ]);
    }


    /**
     * @dataProvider schemaProvider
     */
    public function testBuilder($dataSource, $schema) {
        $this->insertIntoDataSource($dataSource,$schema);
    }


    public function insertIntoDataSource($driverType, $schema)
    {
        $connManager = LazyRecord\ConnectionManager::getInstance();
        $connManager->free();

        $this->registerDataSource($driverType);

        $pdo = $connManager->getConnection($driverType);
        $this->assertInstanceOf('PDO', $pdo);

        $queryDriver = $connManager->getQueryDriver($driverType);
        $builder = SqlBuilder::create($queryDriver,array( 'rebuild' => true ));
        $builder->build($schema);

        $sqls = $builder->build( $schema );
        $this->assertNotEmpty($sqls);
        foreach ($sqls as $sql) {
            $this->assertQueryOK($pdo, $sql);
        }
        $this->assertTableExists($pdo, $schema->getTable());
    }
}

