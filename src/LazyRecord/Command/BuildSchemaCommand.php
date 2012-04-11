<?php
namespace LazyRecord\Command;

use LazyRecord\Schema\SchemaFinder;
use LazyRecord\ConfigLoader;
use LazyRecord\Schema\SchemaGenerator;

/**
 *
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
        $options = $this->getOptions();

        $loader = ConfigLoader::getInstance();
        $loader->load();
        $loader->initForBuild();

        $generator = new SchemaGenerator;
        $generator->setLogger( $logger );

        $args = func_get_args();
        $classes = \LazyRecord\Utils::getSchemaClassFromPathsOrClassNames( 
            $loader,
            $args, 
            $this->logger );
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

