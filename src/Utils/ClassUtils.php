<?php

namespace Maghead\Utils;

use Exception;
use ReflectionClass;
use Doctrine\Common\Inflector\Inflector;

class ClassUtils
{
    public static function get_declared_schema_classes()
    {
        $classes = get_declared_classes();

        return self::filterSchemaClasses($classes);
    }

    public static function filterDeclareSchemaClasses(array $classes)
    {
        return array_filter(function ($class) {
              return is_subclass_of($class, 'Maghead\Schema\DeclareSchema', true);
        }, $classes);
    }

    public static function instantiateSchemaClasses(array $classes)
    {
        return array_map(function ($class) {
            return new $class();
        }, $classes);
    }


    public static function filterExistingClasses(array $classes)
    {
        return array_filter($classes, function ($class) {
            return class_exists($class, true);
        });
    }

    public static function argumentsToSchemaObjects(array $args)
    {
        $classes = ClassUtils::filterExistingClasses($args);
        $classes = array_unique($classes);
        $classes = ClassUtils::filterSchemaClasses($classes);
        return self::instantiateSchemaClasses($classes);
    }

    public static function schema_classes_to_objects(array $classes)
    {
        $classes = self::filterSchemaClasses($classes);
        return self::instantiateSchemaClasses($classes);
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    public static function filterSchemaClasses(array $classes)
    {
        $list = array();
        foreach ($classes as $class) {
            // skip abstract classes.
            if (
              !is_subclass_of($class, 'Maghead\Schema\DeclareSchema', true)
              || is_a($class, 'Maghead\Schema\DynamicSchemaDeclare', true)
              || is_a($class, 'Maghead\Schema\MixinDeclareSchema', true)
              || is_a($class, 'Maghead\Schema\MixinSchemaDeclare', true)
              || is_subclass_of($class, 'Maghead\Schema\MixinDeclareSchema', true)
            ) {
                continue;
            }
            $rf = new ReflectionClass($class);
            if ($rf->isAbstract()) {
                continue;
            }
            $list[] = $class;
        }

        return $list;
    }

}
