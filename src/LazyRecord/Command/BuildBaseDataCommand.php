<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
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

        CommandUtils::print_schema_classes($classes);

        $collection = new SchemaCollection($classes);
        $collection = $collection->evaluate();
        CommandUtils::build_basedata($collection->getSchemas());
        $this->logger->info('Done');
    }
}



