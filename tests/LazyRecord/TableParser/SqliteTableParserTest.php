<?php
use SQLBuilder\Driver\PDOSQLiteDriver;

class SqliteTableParserTest extends PHPUnit_Framework_TestCase
{
    public function testSQLiteTableParser()
    {
        $pdo = new PDO('sqlite::memory:');
        $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
        ok($pdo);

        $pdo->query('CREATE TABLE foo ( id integer primary key autoincrement, name varchar(12), phone varchar(32) unique , address text not null );');
        $pdo->query('CREATE TABLE bar ( id integer primary key autoincrement, confirmed boolean default false, content blob );');

        $parser = new LazyRecord\TableParser\SqliteTableParser(new PDOSQLiteDriver($pdo),$pdo);
        ok($parser);
        ok($parser->getTables());
        count_ok(2,$parser->getTables());

        $sql = $parser->getTableSql('foo');
        ok($sql);

        $columns = $parser->parseTableSql('foo');
        ok($columns);

        $columns = $parser->parseTableSql('bar');
        ok($columns);

        $schema = $parser->getTableSchema('bar');
        ok($schema);

        $column = $schema->getColumn('id');
        ok($column);

        ok($column->autoIncrement);
        is('integer',$column->type);
        is('int',$column->isa);
        is(true,$column->primary);
    }
}

