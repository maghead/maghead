<?php
namespace LazyRecord\Command;
use CLIFramework\Command;
use LazyRecord\Schema;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
use Exception;

class BuildBaseDataCommand extends Command
{

    public function brief() { return 'insert basedata into datasource.'; }

    public function execute()
    {
        $options = $this->options;
        $logger  = $this->logger;

        CommandUtils::set_logger($this->logger);
        CommandUtils::init_config_loader();

        $classes = CommandUtils::find_schemas_with_arguments( func_get_args() );

        CommandUtils::print_schema_classes($classes);

        $schemas = ClassUtils::schema_classes_to_objects( $classes );
        CommandUtils::build_basedata($schemas);

        $this->logger->info('Done');
    }
}



