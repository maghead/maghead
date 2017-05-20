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
    public function testLoadSchemaLoadersWithClassResolver()
    {
        $config = new Config([
            "schema" => [
                "loaders" => [
                    [
                        "name" => "FileSchemaLoader",
                        "args" => [["examples/metric/Model"]],
                    ],
                    [
                        "name" => "ComposerSchemaLoader",
                        "args" => ["composer.json"],
                    ]
                ],
            ],
        ]);
        $loaders = $config->loadSchemaLoaders();
        $this->assertNotEmpty($loaders);
        foreach ($loaders as $loader) {
            $loader->load();
        }
    }

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
