<?php

namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;

class SymbolicLinkConfigLoaderTest extends TestCase
{

    public function setUp()
    {
        if (!file_exists(SymbolicLinkConfigLoader::ANCHOR_FILENAME)) {
            $this->markTestSkipped("require " . SymbolicLinkConfigLoader::ANCHOR_FILENAME);
        }
    }

    public function testLoad()
    {
        $config = SymbolicLinkConfigLoader::load();
        $this->assertInstanceOf('Maghead\\Runtime\\Config\\Config', $config);
    }
}
