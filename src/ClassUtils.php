<?php

namespace Maghead;

use Exception;
use ReflectionClass;
use Doctrine\Common\Inflector\Inflector;
use Maghead\Exception\TableNameConversionException;

class ClassUtils
{
    public static function get_declared_schema_classes()
    {
        $classes = get_declared_classes();

        return self::filter_schema_classes($classes);
    }

    public static function schema_classes_to_objects(array $classes)
    {
        $classes = array_filter($classes, function ($class) {
            return is_subclass_of($class, 'Maghead\\Schema\\DeclareSchema', true);
        });

        return array_map(function ($class) {
            return new $class();
        }, $classes);
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    public static function filter_schema_classes(array $classes)
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

    public static function convertClassToTableName($class)
    {
        if (preg_match('/(\w+?)(?:Model)?$/', $class, $reg)) {
            if (count($reg) < 2) {
                throw new Exception("Can not parse model name: $class");
            }

            /* convert BlahBlah to blah_blah */
            /*
            $table =  strtolower( preg_replace( 
                '/(\B[A-Z])/e' , 
                "'_'.strtolower('$1')" , 
                $table ) );
            */
            $table = $reg[1];
            $table = Inflector::tableize($table);

            return Inflector::pluralize($table);
        } else {
            throw new TableNameConversionException("Table name convert error: $class", $class);
        }
    }
}
