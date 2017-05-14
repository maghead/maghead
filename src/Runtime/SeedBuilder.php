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
            $this->buildSeeds($seeds);
        }
    }

    /**
     * Evaluate seed classes.
     *
     * @param BaseSeed[] $seeds
     */
    public function buildSeeds(array $seeds)
    {
        foreach ($seeds as $seed) {
            $seed::seed();
        }
    }

    /**
     * Build seeds from an array of schema
     */
    public function build($collection)
    {
        foreach ($collection as $s) {
            $this->buildSchemaSeeds($s);
        }
    }
}
