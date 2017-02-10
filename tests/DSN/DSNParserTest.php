<?php
use Maghead\DSN\DSNParser;

/**
 * @group dsn
 */
class DSNParserTest extends PHPUnit_Framework_TestCase
{
    public function dsnProvider()
    {
        return [
            ['odbc:testdb'],
            ['mysql:host=localhost;dbname=testdb'],
            ['mysql:host=localhost;port=3307;dbname=testdb'],
            ['mysql:unix_socket=/tmp/mysql.sock;dbname=testdb'],
            ['pgsql:host=localhost;port=5432;dbname=testdb;user=bruce;password=mypass'],
            ['pgsql:user=exampleuser dbname=exampledb password=examplepass'],
        ];
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
