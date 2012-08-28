<?php
namespace LazyRecord;
use ReflectionClass;

class ClassUtils
{

    static public function get_declared_schema_classes() 
    {
        $classes = get_declared_classes();
        return self::filter_schema_classes($classes);
    }

    /**
     * Get referenced schema classes and put them in order.
     *
     * @param classes[]
     */
    static public function expand_schema_classes($classes)
    {
        $schemas = array();
        foreach( $classes as $class ) {
            $schema = new $class; // declare schema
            $refs = $schema->getReferenceSchemas();
            foreach( $refs as $refClass => $v )
                $schemas[] = $refClass;
            $schemas[] = $class;
        }
        $schemaClasses = array_unique($schemas);
        return array_map(function($class) { 
            return new $class; }, 
                $schemaClasses);
    }

    static public function filter_schema_classes($classes)
    {
        $list = array();
        foreach( $classes as $class ) {
            // skip abstract classes.
            if ( ! is_subclass_of($class, 'LazyRecord\Schema\SchemaDeclare')
              || is_a($class, 'LazyRecord\Schema\MixinSchemaDeclare') 
              || is_subclass_of($class, 'LazyRecord\Schema\MixinSchemaDeclare') 
            ) { 
                continue; 
            }
            $rf = new ReflectionClass( $class );
            if ( $rf->isAbstract() )
                continue;

            $list[] = $class;
        }
        return $list;
    }


}


