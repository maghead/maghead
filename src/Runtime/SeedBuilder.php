<?php

namespace Maghead\Runtime;

use CLIFramework\Logger;
use Maghead\Schema\BaseSchema;
use Maghead\Schema\DeclareSchema;
use Maghead\Runtime\Config\Config;
use Maghead\Utils\ArrayUtils;

use InvalidArgumentException;

class SeedBuilder
{
    protected $logger;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function buildSchemaSeeds(DeclareSchema $schema)
    {
        $seedData = $schema->seeds();
        if (!empty($seedData)) {
            $modelClass = $schema->getModelClass();
            $this->logger->info("Found seeds in $modelClass");
            foreach ($seedData as $seedArg) {
                if (!is_array($seedArg)) {
                    throw new InvalidArgumentException('Seeds data needs to be plain array.');
                }

                $this->logger->debug("Creating seed: " . ArrayUtils::describe($seedArg));
                $ret = $modelClass::create($seedArg);
                if ($ret->error) {
                    $this->logger->error("ERROR: {$ret->message}");
                }
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

    public function buildConfigSeeds(Config $config)
    {
        if ($seeds = $config->getSeedScripts()) {
            foreach ($seeds as $seed) {
                $this->buildScriptSeed($seed);
            }
        }
    }

    public function build($collection)
    {
        foreach ($collection as $s) {
            $this->buildSchemaSeeds($s);
        }
    }
}
