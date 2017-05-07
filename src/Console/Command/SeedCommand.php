<?php

namespace Maghead\Console\Command;

use Maghead\Runtime\SeedBuilder;
use Maghead\Schema\SchemaUtils;
use Maghead\Schema\SchemaCollection;

class SeedCommand extends BaseCommand
{
    public function brief()
    {
        return 'seed data';
    }

    public function aliases()
    {
        return ['sd'];
    }

    public function execute()
    {
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
