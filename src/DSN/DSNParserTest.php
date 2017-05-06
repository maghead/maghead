<?php

namespace Maghead\DSN;

/**
 * @group dsn
 */
class DSNParserTest extends \PHPUnit\Framework\TestCase
{
    public function dsnProvider()
    {
        return [
            ['odbc:testdb'],
            ['mysql:host=localhost;dbname=testdb'],
            ['mysql:host=localhost;port=3307;dbname=testdb'],
            ['mysql:unix_socket=/tmp/mysql.sock;dbname=testdb'],
            ['sqlite:/tmp/testdb.sqlite', 'testdb'],
            ['pgsql:host=localhost;port=5432;dbname=testdb;user=bruce;password=mypass'],
            ['pgsql:user=exampleuser dbname=testdb password=examplepass'],
        ];
    }

    /**
     * @dataProvider dsnProvider
     */
    public function testGetDatabaseName($dsn)
    {
        $dsnObject = DSNParser::parse($dsn);
        $this->assertNotNull($dsnObject);
        $this->assertEquals('testdb', $dsnObject->getDatabaseName());
    }

    /**
     * @dataProvider dsnProvider
     */
    public function testParse($dsn)
    {
        $dsnObject = DSNParser::parse($dsn);
        $this->assertNotNull($dsnObject);
    }
}
