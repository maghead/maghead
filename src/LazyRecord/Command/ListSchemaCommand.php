<?php
namespace LazyRecord\Command;

use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaGenerator;
use CLIFramework\Command;
use ReflectionClass;

/**
 *
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class ListSchemaCommand extends Command
{

    public function usage()
    {
        return 'list-schema [paths|classes]';
    }

    public function brief()
    {
        return 'list schema files.';
    }


    public function execute()
    {
        $logger = $this->getLogger();
        $options = $this->getOptions();

        $loader = ConfigLoader::getInstance();
        $loader->load();
        $loader->initForBuild();

        $this->logger->info("Initializing schema generator...");
        $generator = new SchemaGenerator;
        $generator->setLogger( $logger );

        $args = func_get_args();
        $classes = \LazyRecord\Utils::getSchemaClassFromPathsOrClassNames( 
            $loader,
            $args, 
            $this->logger );

        foreach( $classes as $class ) {
            $rfc = new ReflectionClass($class);
            $this->logger->info( 
                sprintf("  %-25s %s", $class, $rfc->getFilename() ));
        }
        $logger->info('Done');
    }

}

