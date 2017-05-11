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

        $collection = $this->loadSchemasFromArguments(func_get_args());
        $seedBuilder = new SeedBuilder($this->logger);
        $seedBuilder->build($collection);
        $seedBuilder->buildConfigSeeds($config);

        $this->logger->info('Done');
    }
}
