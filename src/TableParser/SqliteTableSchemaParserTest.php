<?php

namespace Maghead\TableParser;

use Maghead\TableParser\SqliteTableSchemaParser;
use PHPUnit\Framework\TestCase;

/**
 * @group table-parser
 */
class SqliteTableSchemaParserTest extends TestCase
{
    public function schemaSqlProvider()
    {
        $data = [];
        $data[] = ['CREATE TABLE foo (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY ASC DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY DESC DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED PRIMARY AUTOINCREMENT DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE `foo` (`a` INT UNSIGNED UNIQUE DEFAULT 0)', 0];
        $data[] = ['CREATE TABLE IF NOT EXISTS `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TEMPORARY TABLE `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 123)', 123];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` BOOLEAN DEFAULT TRUE)', true];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` BOOLEAN DEFAULT FALSE)', false];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 0)', 0];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT 0.1)', 0.1];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT UNSIGNED DEFAULT NULL)', NULL];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20)', -20];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'test\')', 'test'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'t\\\'est\')', 't\\\'est'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` VARCHAR NOT NULL DEFAULT \'t\\\'est\')', 't\\\'est'];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_TIME)', new Token('literal','CURRENT_TIME')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_DATE)', new Token('literal','CURRENT_DATE')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` TIMESTAMP DEFAULT CURRENT_TIMESTAMP)', new Token('literal','CURRENT_TIMESTAMP')];
        $data[] = ['CREATE TEMP TABLE `foo` (`a` INT DEFAULT -20 CONSTRAINT aa UNIQUE(a))', -20];
        return $data;
    }

    /**
     * @dataProvider schemaSqlProvider
     */
    public function testDefaultValueParsing($sql, $exp)
    {
        $parser = new SqliteTableSchemaParser;
        $def = $parser->parse($sql);
        $this->assertObjectHasAttribute('tableName', $def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals($exp, $def->columns[0]->default);

    }


    public function testUnsignedInt()
    {
        $parser = new SqliteTableSchemaParser;
        $def = $parser->parse('CREATE TABLE foo (`a` INT UNSIGNED DEFAULT 123)');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);
        $this->assertEquals('a', $def->columns[0]->name);
        $this->assertEquals('INT', $def->columns[0]->type);
        $this->assertEquals("123", $def->columns[0]->default);
    }


    /**
     * @see https://github.com/c9s/Maghead/issues/94
     */
    public function testForIssue94()
    {
        $parser = new SqliteTableSchemaParser;
        $def = $parser->parse('CREATE TABLE foo (`col4` text DEFAULT \'123\\\'\'\')');
        $this->assertNotNull($def);
        $this->assertEquals('foo', $def->tableName);
        $this->assertCount(1, $def->columns);

        $this->assertEquals('col4', $def->columns[0]->name);
        $this->assertEquals('TEXT', $def->columns[0]->type);
        $this->assertEquals("123\\'", $def->columns[0]->default);
    }
}
