<?php
use LazyRecord\Testing\BaseTestCase;
use LazyRecord\TableParser\TableParser;
use LazyRecord\ConnectionManager;

class TableParserTest extends BaseTestCase
{

    public function getModels() {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AddressSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\BookSchema,
        ];
    }

    /**
     * @dataProvider driverTypeDataProvider
     */
    public function testTableParserFor($driverType)
    {
        $parser = TableParser::create($this->conn, $this->queryDriver);
        $tables = $parser->getTables();
        $this->assertNotNull($tables);
        foreach ($tables as $table) {
            $this->assertNotNull($table);

            $schema = $parser->reverseTableSchema( $table );
            $this->assertNotNull($schema);

            $columns = $schema->getColumns();
            $this->assertNotEmpty($columns);
        }
    }
}

