<?php
use LazyRecord\Sqlbuilder\SqlBuilder;
use LazyRecord\Connection;
use LazyRecord\Testing\ModelTestCase;
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
    public function testBuilder($schema)
    {
        $this->insertIntoDataSource($schema);
    }


    public function insertIntoDataSource($schema)
    {
        $conn = $this->connManager->getConnection(self::getCurrentDriverType());
        $this->assertInstanceOf('PDO', $conn);
        $builder = SqlBuilder::create($this->queryDriver,array( 'rebuild' => true ));

        $sqls = array_filter(array_merge($builder->prepare(), $builder->build($schema), $builder->finalize()));
        $this->assertNotEmpty($sqls);
        foreach ($sqls as $sql) {
            $this->assertQueryOK($conn, $sql);
        }
        $this->assertTableExists($conn, $schema->getTable());
    }
}

