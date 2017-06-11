<?php

namespace AuthorBooks\Model;

use PHPUnit\Framework\TestCase;

use Maghead\Schema\Relationship\HasMany;
use Maghead\Schema\Relationship\Relationship;


class AuthorSchemaTest extends TestCase
{
    public function testForeignRelationship()
    {
        $proxy = new AuthorSchemaProxy;
        $this->assertNotEmpty($proxy->relations);

        $rel = $proxy->relations['author_books'];
        $this->assertInstanceOf(Relationship::class, $rel);
        $this->assertEquals('author_books', $rel->accessor);
    }
}
