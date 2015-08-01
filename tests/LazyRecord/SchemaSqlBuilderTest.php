<?php
use LazyRecord\Sqlbuilder\SqlBuilder;
use LazyRecord\Connection;
use LazyRecord\Testing\BaseTestCase;
use LazyRecord\ConfigLoader;
use LazyRecord\ConnectionManager;

class SqlBuilderTest extends BaseTestCase
{

    public function schemaProvider()
    {
        return [
            [new \AuthorBooks\Model\AuthorSchema],
            [new \AuthorBooks\Model\AddressSchema],
            [new \AuthorBooks\Model\AuthorBookSchema],
            [new \AuthorBooks\Model\BookSchema],
            [new \TestApp\Model\NameSchema],
        ];
    }


    /**
     * @dataProvider schemaProvider
     */
    public function testBuilder($schema) {
        $this->insertIntoDataSource($schema);
    }


    public function insertIntoDataSource($schema)
    {
        $connManager = LazyRecord\ConnectionManager::getInstance();
        $configLoader = ConfigLoader::getInstance();
        $configLoader->loadFromSymbol(true);
        $connManager->init($configLoader);

        $pdo = $connManager->getConnection(self::getCurrentDriverType());
        $this->assertInstanceOf('PDO', $pdo);

        $queryDriver = $connManager->getQueryDriver(self::getCurrentDriverType());
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

