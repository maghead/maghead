<?php
namespace LazyRecord\Command;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Command\CommandUtils;

/**
 * $ lazy clean-schema path/to/Schema path/to/SchemaDir
 *
 */
class CleanSchemaCommand extends \CLIFramework\Command
{

    public function usage()
    {
        return 'clean-schema [paths|classes]';
    }

    public function brief()
    {
        return 'clean up schema files.';
    }

    public function options($opts) 
    {
        $opts->add('f|force','force generate all schema files.');
        parent::options($opts);
    }

    public function execute()
    {
        $logger = $this->getLogger();

        CommandUtils::set_logger($this->logger);
        CommandUtils::init_config_loader();

        $this->logger->debug('Finding schemas...');
        $classes = CommandUtils::find_schemas_with_arguments( func_get_args() );

        CommandUtils::print_schema_classes($classes);

        $this->logger->debug("Initializing schema generator...");
        $generator = new SchemaGenerator;

        if ( $this->options->force ) {
            $generator->setForceUpdate(true);
        }

        $classMap = $generator->generate($classes);
        /*
        foreach( $classMap as $class => $file ) {
            $path = $file;
            if ( strpos( $path , getcwd() ) === 0 ) {
                $path = substr( $path , strlen(getcwd()) + 1 );
            }
            $logger->info($path);
            // $logger->info(sprintf("%-32s",ltrim($class,'\\')) . " => $path",1);
        }
        */
        $logger->info('Done');
    }

}

