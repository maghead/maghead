<?php
namespace Lazy\Command;

use Lazy\Schema\SchemaFinder;
use Lazy\ConfigLoader;
use Lazy\Schema\SchemaGenerator;

/**
 *
 * $ lazy build-schema path/to/Schema path/to/SchemaDir
 *
 */
class BuildSchemaCommand extends \CLIFramework\Command
{

    public function brief()
    {
        return 'build configuration file.';
    }

    public function options($opts)
    {
    }

    public function execute()
    {
        $logger = $this->getLogger();
        $options = $this->getOptions();

        $generator = new SchemaGenerator;
        $generator->setLogger( $logger );

        $loader = ConfigLoader::getInstance();
        $loader->load();
        $loader->init();

        $finder = new SchemaFinder;

        $args = func_get_args();
        if( count($args) ) {
            $finder->paths = $args;
        } elseif( $paths = $loader->getSchemaPaths() ) {
            $finder->paths = $paths;
        }
        $finder->loadFiles();


        // load class from class map
        if( $classMap = $loader->getClassMap() ) {
            foreach( $classMap as $file => $class ) {
                if( ! is_integer($file) && is_string($file) )
                    require $file;
            }
        }

        $classes = $finder->getSchemaClasses();
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

