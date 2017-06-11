<?php

namespace Maghead\Schema\Finder;

use PHPUnit\Framework\TestCase;

class ComposerSchemaFinderTest extends TestCase
{
    public function testLoad()
    {
        $finder = ComposerSchemaFinder::from('composer.json');
        $files = $finder->find();
        $this->assertNotEmpty($files);
    }
}

