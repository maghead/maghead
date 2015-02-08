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
        $config = CommandUtils::init_config_loader();

        $this->logger->debug('Finding schemas...');
        $schemas = CommandUtils::find_schemas_with_arguments( func_get_args() );
        // CommandUtils::print_schema_classes($classes);

        foreach ( $schemas as $schema ) {

            $this->logger->info('Cleaning schema ' . get_class($schema) );
            $paths = array();
            $paths[] = $schema->getRelatedClassPath( $schema->getBaseModelClass() );
            $paths[] = $schema->getRelatedClassPath( $schema->getBaseCollectionClass() );
            $paths[] = $schema->getRelatedClassPath( $schema->getSchemaProxyClass() );

            foreach ( $paths as $path ) {
                $this->logger->info( " - Deleting " . $path );
                if ( file_exists($path) ) {
                    unlink($path);
                }
            }
        }

        /*
        $generator = new SchemaGenerator($config, $this->logger);
        if ( $this->options->force ) {
            $generator->setForceUpdate(true);
        }
        $classMap = $generator->generate($classes);
         */


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

