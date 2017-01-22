<?php
use Maghead\Testing\BaseTestCase;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use Maghead\TableParser\SqliteTableParser;
use Maghead\TableParser\MysqlTableParser;
use Maghead\ConnectionManager;

class MysqlTableParserTest extends BaseTestCase
{
    public $onlyDriver = 'mysql';

    public function testReferenceQuery()
    {
        $schema = new \AuthorBooks\Model\AuthorSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($this->conn, $this->queryDriver, $schema);

        $schema = new \AuthorBooks\Model\BookSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($this->conn, $this->queryDriver, $schema);

        $schema = new \AuthorBooks\Model\AuthorBookSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($this->conn, $this->queryDriver, $schema);

        $parser = new MysqlTableParser($this->conn, $this->queryDriver);
        $references = $parser->queryReferences('books');
        $this->assertNotEmpty($references);
        $this->assertEquals('publishers', $references['publisher_id']->table);
        $this->assertEquals('id', $references['publisher_id']->column);
    }

    public function testReverseSchemaWithStringSet()
    {
        $this->conn->query("DROP TABLE IF EXISTS t1");
        $this->conn->query("CREATE TABLE t1 (val set('a','b','c') );");

        $parser = new MysqlTableParser($this->conn, $this->queryDriver);
        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);

        $column = $schema->getColumn('val');
        $this->assertNotNull($column);
        $this->assertSame(['a','b','c'],$column->set);
    }

    public function testReverseSchemaWithStringEnum()
    {
        $this->conn->query("DROP TABLE IF EXISTS t1");
        $this->conn->query("CREATE TABLE t1 (val enum('ON','OFF','PENDING') );");

        $parser = new MysqlTableParser($this->conn, $this->queryDriver);
        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);

        $column = $schema->getColumn('val');
        $this->assertNotNull($column);
        $this->assertSame(['ON','OFF','PENDING'],$column->enum);
    }

    public function testReverseSchemaAndCompare()
    {
        $schema = new \AuthorBooks\Model\AuthorSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($this->conn, $this->queryDriver, $schema);
        $parser = new MysqlTableParser($this->conn, $this->queryDriver);
        $parser->reverseTableSchema('authors');
    }

    public function testGetTables()
    {
        $this->conn->query("DROP TABLE IF EXISTS t1");
        $this->conn->query("CREATE TABLE t1 (val enum('a','b','c') );");

        $parser = new MysqlTableParser($this->conn, $this->queryDriver);
        $tables = $parser->getTables();
        $this->assertNotEmpty($tables);

        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);
    }
}

