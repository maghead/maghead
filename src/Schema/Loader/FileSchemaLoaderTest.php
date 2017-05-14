<?php

namespace Maghead\Schema\Loader;

use PHPUnit\Framework\TestCase;

class FileSchemaLoaderTest extends TestCase
{
    public function test()
    {
        $loader = new FileSchemaLoader(['tests/apps/AuthorBooks/Model', 'tests/apps/StoreApp/Model']);
        $files = $loader->load();
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
        $this->assertRegExp(FileSchemaLoader::CLASSDECL_PATTERN, $content);
    }
}
