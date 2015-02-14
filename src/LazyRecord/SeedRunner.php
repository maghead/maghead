<?php
namespace LazyRecord;
use CLIFramework\Logger;
use LazyRecord\ConfigLoader;
use LazyRecord\BaseModel;
use LazyRecord\Schema\SchemaBase;
use InvalidArgumentException;

class SeedRunner
{
    public function __construct(ConfigLoader $config, Logger $logger)
    {
        $this->config = $config;
        $this->logger = $logger;
    }

    public function runSchemaSeeds(SchemaBase $schema) 
    {
        if (method_exists($schema,'bootstrap')) {
            if ($modelClass = $schema->getModelClass()) {
                $this->logger->info("Creating base data of $modelClass",'green');
                $schema->bootstrap(new $modelClass);
            }
        }
        if ($seeds = $schema->getSeedClasses()) {
            foreach ($seeds as $seedClass){
                if (class_exists($seedClass,true) ) {
                    $this->logger->info("Running seed script: $seedClass",'green');
                    $seedClass::seed();
                } else {
                    $this->logger->error("ERROR: Seed script $seedClass not found.");
                }
            }
        }
    }

    public function runSeedScript($script) {
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
}




