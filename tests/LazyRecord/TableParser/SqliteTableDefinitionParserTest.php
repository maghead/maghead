<?php
use LazyRecord\TableParser\SqliteTableDefinitionParser;

class SqliteTableDefinitionParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @see https://github.com/c9s/LazyRecord/issues/94
     */
    public function testForIssue94()
    {
        $parser = new SqliteTableDefinitionParser('CREATE TABLE foo (`col4` text DEFAULT \'123\\\'\'\')');
        $def = $parser->parse();
        $this->assertNotNull($def);
        $this->assertEquals('foo',$def->tableName);
        $this->assertCount(1,$def->columns->columns);

        $this->assertEquals('col4',$def->columns->columns[0]->name);
        $this->assertEquals('TEXT',$def->columns->columns[0]->type);
        $this->assertEquals("123\\'",$def->columns->columns[0]->default);
        // echo json_encode( $def , JSON_PRETTY_PRINT);
    }
}
