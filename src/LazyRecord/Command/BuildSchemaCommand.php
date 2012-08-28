<?php
namespace LazyRecord\Command;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\Command\CommandUtils;

/**
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class BuildSchemaCommand extends \CLIFramework\Command
{

    public function usage()
    {
        return 'build-schema [paths|classes]';
    }

    public function brief()
    {
        return 'build configuration file.';
    }


    public function execute()
    {
        $logger = $this->getLogger();

        CommandUtils::set_logger($this->logger);
        CommandUtils::init_config_loader();

        $this->logger->info('Finding schemas...');
        $classes = CommandUtils::find_schemas_with_arguments( func_get_args() );

        CommandUtils::print_schema_classes($classes);

        $this->logger->info("Initializing schema generator...");

        $generator = new SchemaGenerator;
        $generator->setLogger( $logger );
        $classMap = $generator->generate($classes);

        $logger->info('Classmap:');
        foreach( $classMap as $class => $file ) {
            $path = $file;
            if( strpos( $path , getcwd() ) === 0 )
                $path = substr( $path , strlen(getcwd()) + 1 );
            $logger->info(sprintf("%-32s",$class) . " => $path",1);
        }
        $logger->info('Done');
    }

}

