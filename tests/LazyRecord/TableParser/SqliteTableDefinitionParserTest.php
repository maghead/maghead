<?php
use LazyRecord\TableParser\SqliteTableDefinitionParser;

class SqliteTableDefinitionParserTest extends PHPUnit_Framework_TestCase
{
    /**
     * @see https://github.com/c9s/LazyRecord/issues/94
     */
    public function testForIssue94()
    {
        $parser = new SqliteTableDefinitionParser('CREATE TABLE foo (`col4` text DEFAULT \'123\')');
        $def = $parser->parse();
        echo json_encode( $def , JSON_PRETTY_PRINT); 
    }
}

