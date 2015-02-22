<?php
namespace LazyRecord;
use CLIFramework\Logger;
use LazyRecord\ConfigLoader;
use LazyRecord\BaseModel;
use LazyRecord\Schema\SchemaBase;
use LazyRecord\Schema\SchemaCollection;
use InvalidArgumentException;

class SeedBuilder
{
    public function __construct(ConfigLoader $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function buildSchemaSeeds(SchemaBase $schema) 
    {
        if (method_exists($schema,'bootstrap')) {
            if ($modelClass = $schema->getModelClass()) {
                $this->logger->info("Creating base data of $modelClass");
                $schema->bootstrap(new $modelClass);
            }
        }
        if ($seeds = $schema->getSeedClasses()) {
            foreach ($seeds as $seedClass){
                if (class_exists($seedClass,true) ) {
                    $this->logger->info("Running seed script: $seedClass");
                    $seedClass::seed();
                } else {
                    $this->logger->error("ERROR: Seed script $seedClass not found.");
                }
            }
        }
    }

    public function buildScriptSeed($script) {
        if (file_exists($seed)) {
            return require $seed;
        } else {
            $seed = str_replace('::','\\',$seed);
            if (class_exists($seed,true)) {
                return $seed::seed();
            }
        }
        throw new InvalidArgumentException("Invalid seed script name");
    }

    public function buildConfigSeeds() {
        if ($seeds = $this->config->getSeedScripts()) {
            foreach( $seeds as $seed ) {
                $this->buildScriptSeed($seed);
            }
        }
    }

    public function build(SchemaCollection $collection) {
        foreach($collection as $s) {
            $this->buildSchemaSeeds($s);
        }
        $this->buildConfigSeeds();
    }

}




