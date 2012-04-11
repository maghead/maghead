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

    public function getSchemaClassFromPathsOrClassNames($args,$logger = null)
    {
        $classes = array();
        if( count($args) && ! file_exists($args[0]) ) {
            // it's classnames
            foreach( $args as $class ) {
                // call class loader to load
                if( class_exists($class,true) ) {
                    $classes[] = $class;
                }
                else {
                    if( $logger )
                        $logger->warn( "$class not found." );
                    else
                        echo ">>> $class not found.\n";
                }
            }
        }
        else {
            $finder = new SchemaFinder;
            if( count($args) && file_exists($args[0]) ) {
                $finder->paths = $args;
            } 
            // load schema paths from config
            elseif( $paths = $loader->getSchemaPaths() ) {
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
        }
        return $classes;
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
        $classes = $this->getSchemaClassFromPathsOrClassNames( $args , $this->logger );
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

