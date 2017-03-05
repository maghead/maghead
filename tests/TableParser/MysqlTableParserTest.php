<?php
use Maghead\Testing\BaseTestCase;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use Maghead\TableParser\SqliteTableParser;
use Maghead\TableParser\MysqlTableParser;

/**
 * @group mysql
 * @group table-parser
 */
class MysqlTableParserTest extends BaseTestCase
{
    protected $onlyDriver = 'mysql';

    public function testReferenceQuery()
    {
        $conn = $this->getMasterConnection();

        $schema = new \AuthorBooks\Model\AuthorSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($conn, $conn->getQueryDriver(), $schema);

        $schema = new \AuthorBooks\Model\BookSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($conn, $conn->getQueryDriver(), $schema);

        $schema = new \AuthorBooks\Model\AuthorBookSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($conn, $conn->getQueryDriver(), $schema);

        $parser = new MysqlTableParser($conn, $conn->getQueryDriver());
        $references = $parser->queryReferences('books');
        $this->assertNotEmpty($references);
        $this->assertEquals('publishers', $references['publisher_id']->table);
        $this->assertEquals('id', $references['publisher_id']->column);
    }

    public function testReverseSchemaWithStringSet()
    {
        $conn = $this->getMasterConnection();

        $conn->query("DROP TABLE IF EXISTS t1");
        $conn->query("CREATE TABLE t1 (val set('a','b','c') );");

        $parser = new MysqlTableParser($conn, $conn->getQueryDriver());
        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);

        $column = $schema->getColumn('val');
        $this->assertNotNull($column);
        $this->assertSame(['a','b','c'], $column->set);
    }

    public function testReverseSchemaWithStringEnum()
    {
        $conn = $this->getMasterConnection();

        $conn->query("DROP TABLE IF EXISTS t1");
        $conn->query("CREATE TABLE t1 (val enum('ON','OFF','PENDING') );");

        $parser = new MysqlTableParser($conn, $conn->getQueryDriver());
        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);

        $column = $schema->getColumn('val');
        $this->assertNotNull($column);
        $this->assertSame(['ON','OFF','PENDING'], $column->enum);
    }

    public function testReverseSchemaAndCompare()
    {
        $conn = $this->getMasterConnection();

        $schema = new \AuthorBooks\Model\AuthorSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($conn, $conn->getQueryDriver(), $schema);
        $parser = new MysqlTableParser($conn, $conn->getQueryDriver());
        $parser->reverseTableSchema('authors');
    }

    public function testGetTables()
    {
        $conn = $this->getMasterConnection();

        $conn->query("DROP TABLE IF EXISTS t1");
        $conn->query("CREATE TABLE t1 (val enum('a','b','c') );");

        $parser = new MysqlTableParser($conn, $conn->getQueryDriver());
        $tables = $parser->getTables();
        $this->assertNotEmpty($tables);

        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);
    }
}
