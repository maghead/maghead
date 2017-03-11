<?php
use SQLBuilder\Driver\PDOSQLiteDriver;
use Maghead\TableParser\SqliteTableParser;

/**
 * @group table-parser
 */
class SqliteTableParserTest extends PHPUnit\Framework\TestCase
{



    public function testParsingQuotedIdentifier()
    {
        $conn = new PDO('sqlite::memory:');
        $defsql = "CREATE TABLE foo (`uuid` BINARY(16) NOT NULL PRIMARY KEY, `name` varchar(12))";
        $conn->query($defsql);

        $parser = new SqliteTableParser($conn, new PDOSQLiteDriver($conn));
        $tables = $parser->getTables();

        $sql = $parser->getTableSql('foo');
        $this->assertEquals($defsql, $sql);
        $result = $parser->parseTableSql('foo');
        $columns = $result->columns;

        list($uuid, $name) = $columns;

        $this->assertNotEmpty($columns);
        $this->assertCount(2, $columns);
        $this->assertEquals('uuid', $uuid->name);
        $this->assertEquals('BINARY', $uuid->type);
        $this->assertEquals(16, $uuid->length);
        $this->assertTrue($uuid->primary);

        $this->assertEquals('name', $name->name);
        $this->assertEquals('VARCHAR', $name->type);
        $this->assertEquals(12, $name->length);
    }


    public function testSQLiteTableParser()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        $pdo->query('CREATE TABLE foo ( id integer primary key autoincrement, name varchar(12), phone varchar(32) unique , address text not null );');
        $pdo->query('CREATE TABLE bar ( id integer primary key autoincrement, confirmed boolean default false, content blob );');

        $parser = new SqliteTableParser($pdo, new PDOSQLiteDriver($pdo));
        $tables = $parser->getTables();

        $this->assertNotEmpty($tables);
        $this->assertCount(2, $tables);

        $sql = $parser->getTableSql('foo');
        $columns = $parser->parseTableSql('foo');
        $this->assertNotEmpty($columns);

        $columns = $parser->parseTableSql('bar');
        $this->assertNotEmpty($columns);

        $schema = $parser->reverseTableSchema('bar');
        $this->assertNotNull($schema);

        $id = $schema->getColumn('id');
        $this->assertNotNull($id);
        $this->assertTrue($id->autoIncrement);
        $this->assertEquals('INT', $id->type);
        $this->assertEquals('int', $id->isa);
        $this->assertTrue($id->primary);
    }
}
