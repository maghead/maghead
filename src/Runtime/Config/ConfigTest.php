<?php

namespace Maghead\Runtime\Config;

use PHPUnit\Framework\TestCase;
use Maghead\Runtime\BaseSeed;

class TestSeed extends BaseSeed
{
    public static function seed()
    {
    }
}

class ConfigTest extends TestCase
{
    public function testLoadSeedScripts()
    {
        $config = new Config([
            "seeds" => [
                'Maghead\\Runtime\\Config\\TestSeed',
            ],
        ]);
        $seeds = $config->loadSeedScripts();
        $this->assertNotEmpty($seeds);
    }
}
