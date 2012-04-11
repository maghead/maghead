<?php
namespace LazyRecord;

class Utils
{
    static function getSchemaClassFromPathsOrClassNames($args,$logger = null)
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




}

