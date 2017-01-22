<?php

namespace Maghead;

use CLIFramework\Logger;
use Maghead\Schema\SchemaBase;
use Maghead\Schema\SchemaCollection;
use InvalidArgumentException;

class SeedBuilder
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function buildSchemaSeeds(SchemaBase $schema)
    {
        if (method_exists($schema, 'bootstrap')) {
            if ($modelClass = $schema->getModelClass()) {
                $this->logger->info("Creating base data of $modelClass");
                $schema->bootstrap(new $modelClass());
            }
        }
        if ($seeds = $schema->getSeedClasses()) {
            foreach ($seeds as $seedClass) {
                if (class_exists($seedClass, true)) {
                    $this->logger->info("Seeding: $seedClass");
                    $seedClass::seed();
                } else {
                    $this->logger->error("ERROR: Seed script $seedClass not found.");
                }
            }
        }
    }

    public function buildScriptSeed($script)
    {
        if (file_exists($seed)) {
            return require $seed;
        } else {
            $seed = str_replace('::', '\\', $seed);
            if (class_exists($seed, true)) {
                return $seed::seed();
            }
        }
        throw new InvalidArgumentException('Invalid seed script name');
    }

    public function buildConfigSeeds(ConfigLoader $config)
    {
        if ($seeds = $config->getSeedScripts()) {
            foreach ($seeds as $seed) {
                $this->buildScriptSeed($seed);
            }
        }
    }

    public function build(SchemaCollection $collection)
    {
        $collection = $collection->evaluate();
        foreach ($collection as $s) {
            $this->buildSchemaSeeds($s);
        }
    }
}
