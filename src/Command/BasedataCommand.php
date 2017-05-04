<?php

namespace Maghead\Command;

use Maghead\SeedBuilder;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaCollection;

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
        $config = $this->getConfig();

        $classes = $this->findSchemasByArguments(func_get_args());

        SchemaUtils::printSchemaClasses($classes, $this->logger);

        $collection = new SchemaCollection($classes);
        $collection = $collection->evaluate();

        $seedBuilder = new SeedBuilder($this->logger);
        $seedBuilder->build($collection);
        $seedBuilder->buildConfigSeeds($config);

        $this->logger->info('Done');
    }
}
