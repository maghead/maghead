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
}

