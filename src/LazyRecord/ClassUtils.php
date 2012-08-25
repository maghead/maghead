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


