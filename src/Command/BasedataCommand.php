<?php

namespace LazyRecord\Command;

use LazyRecord\SeedBuilder;
use LazyRecord\Schema\SchemaUtils;
use LazyRecord\Schema\SchemaCollection;

class BasedataCommand extends BaseCommand
{
    public function brief()
    {
        return 'insert basedata into datasource.';
    }

    public function execute()
    {
        $options = $this->options;
        $logger = $this->logger;

        $classes = SchemaUtils::findSchemasByArguments($this->getConfigLoader(), func_get_args(), $this->logger);

        SchemaUtils::printSchemaClasses($classes, $this->logger);

        $collection = new SchemaCollection($classes);
        $collection = $collection->evaluate();

        $seedBuilder = new SeedBuilder($this->getConfigLoader(), $this->logger);
        $seedBuilder->build($collection);

        $this->logger->info('Done');
    }
}
