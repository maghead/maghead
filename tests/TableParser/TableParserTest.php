<?php
use Maghead\Testing\BaseTestCase;
use Maghead\Testing\ModelTestCase;
use Maghead\TableParser\TableParser;

/**
 * @group table-parser
 */
class TableParserTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new \AuthorBooks\Model\AuthorSchema,
            new \AuthorBooks\Model\AddressSchema,
            new \AuthorBooks\Model\AuthorBookSchema,
            new \AuthorBooks\Model\BookSchema,
        ];
    }


    public function tableNameProvider()
    {
        $models = $this->getModels();
        return array_map(function($schema) {
            return [$schema->getTable()];
        }, $models);
    }


    /**
     * @dataProvider tableNameProvider
     */
    public function testTableParserFor($table)
    {
        $parser = TableParser::create($this->conn, $this->queryDriver);
        $schema = $parser->reverseTableSchema($table);
        $this->assertNotNull($schema);
        $this->assertInstanceOf('Maghead\\Schema\\DeclareSchema', $schema);

        $columns = $schema->getColumns();
        $this->assertNotEmpty($columns);
    }
}
