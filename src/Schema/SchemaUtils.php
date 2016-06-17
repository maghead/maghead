<?php

namespace LazyRecord\Schema;

use CLIFramework\Logger;
use LazyRecord\ConfigLoader;
use LazyRecord\ClassUtils;
use ReflectionObject;

class SchemaUtils
{
    public static function printSchemaClasses(array $classes, Logger $logger = null)
    {
        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger = $c['logger'];
        }
        $logger->info('Schema classes:');
        foreach ($classes as $class) {
            $logger->info($logger->formatter->format($class, 'green'), 1);
        }
    }

    /*
    static public function find_schema_parents(array $classes)
    {
        $parents = [];
        foreach ($classes as $class) {
            $schema = new $class; // declare schema
            foreach ($schema->relations as $relKey => $rel ) {
                if (!isset($rel['foreign_schema'])) {
                    continue;
                }
                $foreignClass = ltrim($rel['foreign_schema'],'\\');
                $schema = new $foreignClass;
                if ($rel->type == Relationship::BELONGS_TO) {
                    $parents[$class][] = $foreignClass;
                } else if ($rel->type == Relationship::HAS_ONE || $rel->type == Relationship::HAS_MANY) {
                    $parents[$foreignClass][] = $class;
                }
            }
        }
        return $parents;
    }
     */

    /**
     * Get referenced schema classes and put them in order.
     *
     * @param string[] schema objects
     */
    public static function expandSchemaClasses(array $classes)
    {
        $map = array();
        $schemas = array();
        foreach ($classes as $class) {
            $schema = new $class(); // declare schema

            if ($refs = $schema->getReferenceSchemas()) {
                foreach ($refs as $refClass => $v) {
                    if (isset($map[$refClass])) {
                        continue;
                    }
                    $schemas[] = new $refClass();
                    $map[$refClass] = true;
                }
            }

            if ($schema instanceof TemplateSchema) {
                $expandedSchemas = $schema->provideSchemas();
                foreach ($expandedSchemas as $expandedSchema) {
                    if (isset($map[get_class($expandedSchema)])) {
                        continue;
                    }
                    $schemas[] = $expandedSchema;
                    $map[get_class($expandedSchema)] = true;
                }
            } else {
                if (isset($map[$class])) {
                    continue;
                }
                $schemas[] = $schema;
                $map[$class] = true;
            }
        }

        return $schemas;
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    public static function filterBuildableSchemas(array $schemas)
    {
        $list = array();
        foreach ($schemas as $schema) {
            // skip abstract classes.
            if ($schema instanceof DynamicSchemaDeclare
                || $schema instanceof MixinDeclareSchema
                || (!$schema instanceof SchemaDeclare && !$schema instanceof DeclareSchema)
            ) {
                continue;
            }

            $rf = new ReflectionObject($schema);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $schema;
        }

        return $list;
    }

    /**
     * @param ConfigLoader $loader
     * @param Logger       $logger
     */
    public static function findSchemasByConfigLoader(ConfigLoader $loader, Logger $logger = null)
    {
        if ($paths = $loader->getSchemaPaths()) {
            $finder = new SchemaFinder();
            $finder->setPaths($loader->getSchemaPaths());
            $finder->find();
        }

        // load class from class map
        if ($classMap = $loader->getClassMap()) {
            foreach ($classMap as $file => $class) {
                if (!is_integer($file) && is_string($file)) {
                    require $file;
                }
            }
        }

        return SchemaLoader::loadDeclaredSchemas();
    }

    /**
     * Returns schema objects.
     *
     * @return array schema objects
     */
    public static function findSchemasByArguments(ConfigLoader $loader, array $args, Logger $logger = null)
    {
        if (count($args) && !file_exists($args[0])) {
            $classes = array();
            // it's classnames
            foreach ($args as $class) {
                // call class loader to load
                if (class_exists($class, true)) {
                    $classes[] = $class;
                } else {
                    if ($logger) {
                        $logger->warn("$class not found.");
                    } else {
                        echo ">>> $class not found.\n";
                    }
                }
            }

            return ClassUtils::schema_classes_to_objects(array_unique($classes));
        } else {
            $finder = new SchemaFinder();
            if (count($args) && file_exists($args[0])) {
                $finder->setPaths($args);
                foreach ($args as $file) {
                    if (is_file($file)) {
                        require_once $file;
                    }
                }
            }
            // load schema paths from config
            elseif ($paths = $loader->getSchemaPaths()) {
                $finder->setPaths($paths);
            }
            $finder->find();

            // load class from class map
            if ($classMap = $loader->getClassMap()) {
                foreach ($classMap as $file => $class) {
                    if (!is_integer($file) && is_string($file)) {
                        require $file;
                    }
                }
            }

            return SchemaLoader::loadDeclaredSchemas();
        }
    }
}
