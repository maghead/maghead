<?php
namespace LazyRecord\Schema;
use CLIFramework\Logger;
use LazyRecord\Schema\SchemaFinder;
use LazyRecord\Schema\DeclareSchema;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Schema\MixinDeclareSchema;
use LazyRecord\Schema\TemplateSchema;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;

use ReflectionObject;
use ReflectionMethod;

class SchemaUtils
{
    static public function printSchemaClasses(array $classes, Logger $logger = NULL) 
    {
        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger = $c['logger'];
        }

        $logger->info('Schema classes:');
        foreach( $classes as $class ) {
            $logger->info($logger->formatter->format($class, 'green') , 1);
        }
    }


    /**
     * Get referenced schema classes and put them in order.
     *
     * @param string[] schema objects
     */
    static public function expandSchemaClasses(array $classes)
    {
        $map = array();
        $schemas = array();
        foreach ($classes as $class) {
            $schema = new $class; // declare schema
            $map[$class] = TRUE;

            if ($refs = $schema->getReferenceSchemas()) {
                foreach ($refs as $refClass => $v) {
                    if (isset($map[$refClass])) {
                        continue;
                    }
                    $schemas[] = new $refClass;
                    $map[$refClass] = TRUE;
                }
            }

            if ($schema instanceof TemplateSchema) {
                $expandedSchemas = $schema->provideSchemas();
                foreach ($expandedSchemas as $expandedSchema) {
                    $schemas[] = $expandedSchema;
                    $map[get_class($expandedSchema)] = TRUE;
                }
            } else {
                $schemas[] = $schema;
            }
        }
        return $schemas;
    }



    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    static public function filterBuildableSchemas(array $schemas)
    {
        $list = array();
        foreach ($schemas as $schema) {
            // skip abstract classes.
            if (   $schema instanceof DynamicSchemaDeclare 
                || $schema instanceof MixinDeclareSchema 
                || (! $schema instanceof SchemaDeclare && ! $schema instanceof DeclareSchema)
            ) { continue; }

            $rf = new ReflectionObject($schema);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $schema;
        }
        return $list;
    }


    /**
     *
     * @param ConfigLoader $loader
     * @param Logger $logger
     */
    static public function findSchemasByConfigLoader(ConfigLoader $loader, Logger $logger = null)
    {
        $finder = new SchemaFinder;
        $finder->paths = $loader->getSchemaPaths();
        $finder->find();

        // load class from class map
        if ($classMap = $loader->getClassMap()) {
            foreach ($classMap as $file => $class) {
                if (! is_integer($file) && is_string($file)) {
                    require $file;
                }
            }
        }
        return $finder->getSchemas();
    }


    /**
     * Returns schema objects
     *
     * @return array schema objects
     */
    static public function findSchemasByArguments(ConfigLoader $loader, array $args, Logger $logger = null)
    {
        if (count($args) && ! file_exists($args[0])) {
            $classes = array();
            // it's classnames
            foreach ($args as $class) {
                // call class loader to load
                if (class_exists($class,true)) {
                    $classes[] = $class;
                } else {
                    if ($logger) {
                        $logger->warn( "$class not found." );
                    } else {
                        echo ">>> $class not found.\n";
                    }
                }
            }
            return ClassUtils::schema_classes_to_objects($classes);
        } else {
            $finder = new SchemaFinder;
            if (count($args) && file_exists($args[0])) {
                $finder->setPaths($args);
                foreach ($args as $file) {
                    if (is_file($file) ) {
                        require_once $file;
                    }
                }
            } 
            // load schema paths from config
            else if( $paths = $loader->getSchemaPaths() ) {
                $finder->setPaths($paths);
            }
            $finder->find();

            // load class from class map
            if ($classMap = $loader->getClassMap()) {
                foreach ($classMap as $file => $class ) {
                    if (! is_integer($file) && is_string($file)) {
                        require $file;
                    }
                }
            }
            return $finder->getSchemas();
        }
    }
}



