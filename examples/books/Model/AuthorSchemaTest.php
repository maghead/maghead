<?php

namespace AuthorBooks\Model;

use PHPUnit\Framework\TestCase;

class AuthorSchemaTest extends TestCase
{
    public function testForeignRelationship()
    {
        $proxy = new AuthorSchemaProxy;
        $this->assertNotEmpty($proxy->relations);
    }
}
