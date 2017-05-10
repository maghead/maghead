<?php

namespace Maghead\DSN;

use PHPUnit\Framework\TestCase;

class DSNTest extends TestCase
{
    public function testRemoveDB()
    {
        $dsn = new DSN('mysql',[ 'host' => 'localhost', 'dbname' => 'aaa']);
        $this->assertTrue($dsn->offsetExists('dbname'));
        $dsn->removeDBName();
        $this->assertFalse($dsn->offsetExists('dbname'));
    }
}



