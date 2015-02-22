<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
use LazyRecord\SeedBuilder;
use LazyRecord\Schema\SchemaUtils;
use Exception;
use LazyRecord\Schema\SchemaCollection;

class BuildBaseDataCommand extends BaseCommand
{

    public function brief() { return 'insert basedata into datasource.'; }

    public function execute()
    {
        $options = $this->options;
        $logger  = $this->logger;

        CommandUtils::set_logger($this->logger);

        $classes = $this->findSchemasByArguments( func_get_args() );

        SchemaUtils::printSchemaClasses($this->logger, $classes);

        $collection = new SchemaCollection($classes);
        $collection = $collection->evaluate();

        $seedBuilder = new SeedBuilder($this->getConfigLoader(), $this->logger);
        $seedBuilder->build($collection);

        $this->logger->info('Done');
    }
}



