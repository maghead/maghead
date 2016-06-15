<?php
use LazyRecord\Testing\BaseTestCase;
use SQLBuilder\Driver\PDOSQLiteDriver;
use SQLBuilder\Driver\PDOMySQLDriver;
use LazyRecord\TableParser\SqliteTableParser;
use LazyRecord\TableParser\MysqlTableParser;
use LazyRecord\ConnectionManager;

class MysqlTableParserTest extends BaseTestCase
{


    public function setUp()
    {
        if ($this->getDriverType() != 'mysql') {
            return $this->markTestSkipped('skip mysql tests');
        }
        parent::setUp();
    }


    public function testReverseSchemaWithStringSet()
    {
        $manager = ConnectionManager::getInstance();
        $conn = $manager->getConnection('mysql');
        $driver = $manager->getQueryDriver('mysql');

        $conn->query("DROP TABLE IF EXISTS t1");
        $conn->query("CREATE TABLE t1 (val set('a','b','c') );");

        $parser = new MysqlTableParser($driver, $conn);
        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);

        $column = $schema->getColumn('val');
        $this->assertNotNull($column);
        $this->assertSame(['a','b','c'],$column->set);
    }

    public function testReverseSchemaWithStringEnum()
    {
        $manager = ConnectionManager::getInstance();
        $conn = $manager->getConnection('mysql');
        $driver = $manager->getQueryDriver('mysql');

        $conn->query("DROP TABLE IF EXISTS t1");
        $conn->query("CREATE TABLE t1 (val enum('ON','OFF','PENDING') );");

        $parser = new MysqlTableParser($driver, $conn);
        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);

        $column = $schema->getColumn('val');
        $this->assertNotNull($column);
        $this->assertSame(['ON','OFF','PENDING'],$column->enum);
    }

    public function testReverseSchemaAndCompare()
    {
        $manager = ConnectionManager::getInstance();
        $conn = $manager->getConnection('mysql');
        $driver = $manager->getQueryDriver('mysql');

        $schema = new \AuthorBooks\Model\AuthorSchema;
        $this->updateSchemaFiles($schema);
        $this->buildSchemaTable($driver, $conn, $schema);

        $parser = new MysqlTableParser($driver, $conn);
        $parser->reverseTableSchema('authors');
    }

    public function testGetTables()
    {
        $manager = ConnectionManager::getInstance();
        $conn = $manager->getConnection('mysql');
        $driver = $manager->getQueryDriver('mysql');

        $conn->query("DROP TABLE IF EXISTS t1");
        $conn->query("CREATE TABLE t1 (val enum('a','b','c') );");

        $parser = new MysqlTableParser($driver, $conn);
        $tables = $parser->getTables();
        $this->assertNotEmpty($tables);

        $schema = $parser->reverseTableSchema('t1');
        $this->assertNotNull($schema);
        /*
        $sql = $parser->getTableSql('foo');
        ok($sql);

        $columns = $parser->parseTableSql('foo');
        $this->assertNotEmpty($columns);

        $columns = $parser->parseTableSql('bar');
        $this->assertNotEmpty($columns);

        $schema = $parser->reverseTableSchema('bar');
        $this->assertNotNull($schema);

        $id = $schema->getColumn('id');
        $this->assertNotNull($id);
        $this->assertTrue($id->autoIncrement);
        $this->assertEquals('INTEGER',$id->type);
        $this->assertEquals('int',$id->isa);
        $this->assertTrue($id->primary);
         */
    }
}

