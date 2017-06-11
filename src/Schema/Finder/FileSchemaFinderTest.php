<?php

namespace Maghead\Schema\Finder;

use PHPUnit\Framework\TestCase;

class FileSchemaFinderTest extends TestCase
{
    public function test()
    {
        $finder = new FileSchemaFinder(['tests/apps/AuthorBooks/Model', 'tests/apps/StoreApp/Model']);
        $files = $finder->find();
        $this->assertNotEmpty($files);
    }

    public function classDeclProvider()
    {
        $data = [];
        $data[] = ["FooSchema extends DeclareSchema"];
        $data[] = ["FooSchema extends\nDeclareSchema"];
        $data[] = ["FooSchema\nextends\nDeclareSchema"];
        $data[] = ["FooSchema extends\nMaghead\\Schema\\DeclareSchema"];
        $data[] = ["FooSchema extends\nMaghead\\Schema\\MixinSchema"];
        $data[] = ["PowerUserSchema extends UserSchema"];
        return $data;
    }


    /**
     * @dataProvider classDeclProvider
     */
    public function testClassDeclPattern($content)
    {
        $this->assertRegExp(FileSchemaFinder::CLASSDECL_PATTERN, $content);
    }
}
