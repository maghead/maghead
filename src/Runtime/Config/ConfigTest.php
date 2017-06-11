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
                "finders" => [
                    [
                        "name" => "FileSchemaFinder",
                        "args" => [["examples/metric/Model"]],
                    ],
                    [
                        "name" => "ComposerSchemaFinder",
                        "args" => ["composer.json"],
                    ]
                ],
            ],
        ]);
        $finders = $config->loadSchemaFinders();
        $this->assertNotEmpty($finders);
        foreach ($finders as $finder) {
            $finder->find();
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
