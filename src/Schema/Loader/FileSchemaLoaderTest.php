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
}
