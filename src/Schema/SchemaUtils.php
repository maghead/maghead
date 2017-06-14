<?php

namespace Maghead\Schema;

use CLIFramework\Logger;
use Maghead\Runtime\Config\Config;
use Maghead\Schema\Finder\FileSchemaFinder;
use ReflectionObject;
use ReflectionClass;

class SchemaUtils
{
    /**
     * Get referenced schema classes and put them in order.
     *
     * @param string[] schema objects
     */
    public static function expandSchemas(SchemaCollection $collection)
    {
        $map = [];
        $schemas = [];
        foreach ($collection->evaluate() as $schema) {

            // expand reference
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
                $class = get_class($schema);
                if (isset($map[$class])) {
                    continue;
                }
                $schemas[] = $schema;
                $map[$class] = true;
            }
        }

        return $schemas;
    }

    public static function findSchemasByPaths(array $paths = null)
    {
        if ($paths && !empty($paths)) {
            $finder = new FileSchemaFinder($paths);
            $loadedFiles = $finder->find();
        }

        return SchemaLoader::loadDeclaredSchemas();
    }

    /**
     * Use the given config object to load schema files.
     *
     * @param Config       $config
     */
    public static function findSchemasByConfig(Config $config)
    {
        return self::findSchemasByPaths($config->getSchemaPaths());
    }

    /**
     * Given a list of schema object,
     * return the schema objects that are defined with shard mapping
     */
    public static function filterShardMappingSchemas($mappingId, array $schemas)
    {
        $mappingIds = (array) $mappingId;
        return array_filter($schemas, function (DeclareSchema $s) use ($mappingIds) {
            return in_array($s->shardMapping, $mappingIds);
        });
    }
}
