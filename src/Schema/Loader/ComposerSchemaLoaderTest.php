<?php

namespace Maghead\Schema\Loader;

use PHPUnit\Framework\TestCase;

class ComposerSchemaLoaderTest extends TestCase
{
    public function testLoad()
    {
        $loader = ComposerSchemaLoader::from('composer.json');
        $files = $loader->load();
        $this->assertNotEmpty($files);
    }

    public function classDeclProvider()
    {
        $data = [];
        $data[] = ["Foo extends DeclareSchema"];
        $data[] = ["Foo extends\nDeclareSchema"];
        $data[] = ["Foo extends\nMaghead\\Schema\\DeclareSchema"];
        $data[] = ["Foo extends\nMaghead\\Schema\\MixinSchema"];
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

