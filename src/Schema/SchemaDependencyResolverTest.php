<?php

namespace Maghead\Schema;

use AuthorBooks\Model\AuthorSchema;
use AuthorBooks\Model\AuthorBookSchema;
use AuthorBooks\Model\BookSchema;
use AuthorBooks\Model\CategorySchema;
use AuthorBooks\Model\AddressSchema;
use AuthorBooks\Model\PublisherSchema;
use PHPUnit\Framework\TestCase;
use CLIFramework\Logger;

class SchemaDependencyResolverTest extends TestCase
{
    public function testResolve()
    {
        $schemas = SchemaCollection::create([
            new AddressSchema,
            new AuthorBookSchema,
            new AuthorSchema,
            new BookSchema,
        ]);

        $logger = new Logger;
        $logger->setQuiet();

        $resolver = new SchemaDependencyResolver($logger);
        $resolved = $resolver->resolve($schemas);

        $classes = $resolved->classes()->getArrayCopy();
        $this->assertEquals([
            AuthorSchema::class,
            AddressSchema::class,
            PublisherSchema::class,
            CategorySchema::class,
            BookSchema::class,
            AuthorBookSchema::class,
        ], $classes);
    }
}
