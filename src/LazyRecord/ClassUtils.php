<?php
namespace LazyRecord;
use Exception;
use RuntimeException;
use ReflectionClass;
use LazyRecord\Inflector;
use LazyRecord\Schema\DynamicSchemaDeclare;

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
     * @param string[] schema objects
     */
    static public function expand_schema_classes($classes)
    {
        $schemas = array();
        foreach( $classes as $class ) {
            $schema = new $class; // declare schema
            $refs = $schema->getReferenceSchemas();
            foreach ( $refs as $refClass => $v ) {
                $schemas[] = $refClass;
            }
            $schemas[] = $class;
        }
        $schemaClasses = array_unique($schemas);
        return self::schema_classes_to_objects($schemaClasses);
    }


    public static function schema_classes_to_objects($classes) {
        $schemas = array();
        foreach( $classes as $class ) {
            if( is_a($class,'LazyRecord\BaseModel',true) ) {
                // TODO: refactor this to a factory method
                $model = new $class;
                $schemas[] = new \LazyRecord\Schema\DynamicSchemaDeclare($model);
            }
            elseif( is_subclass_of($class,'LazyRecord\Schema\SchemaDeclare',true) ) {
                $schemas[] = new $class; 
            }
        }
        return $schemas;
    }

    /**
     * Filter non-dynamic schema declare classes.
     *
     * @param string[] $classes class list.
     */
    static public function filter_schema_classes($classes)
    {
        $list = array();
        foreach( $classes as $class ) {
            // skip abstract classes.
            if (
              ! is_subclass_of($class, 'LazyRecord\Schema\SchemaDeclare',true)
              || is_a($class, 'LazyRecord\Schema\DynamicSchemaDeclare',true)
              || is_a($class, 'LazyRecord\Schema\MixinSchemaDeclare',true)
              || is_subclass_of($class, 'LazyRecord\Schema\MixinSchemaDeclare',true)
            ) { 
                continue; 
            }
            $rf = new ReflectionClass( $class );
            if ( $rf->isAbstract() ) {
                continue;
            }

            $list[] = $class;
        }
        return $list;
    }

    static public function convert_class_to_table($class)
    {
        if( preg_match( '/(\w+?)(?:Model)?$/', $class ,$reg) ) {
            if ( count($reg) < 2 ) {
                throw new Exception( "Can not parse model name: $class" );
            }

            /* convert BlahBlah to blah_blah */
            /*
            $table =  strtolower( preg_replace( 
                '/(\B[A-Z])/e' , 
                "'_'.strtolower('$1')" , 
                $table ) );
            */
            $table = $reg[1];
            $inflector = Inflector::getInstance();
            $table = $inflector->underscore($table);
            return $inflector->pluralize($table);
        } else {
            throw new Exception('Table name convert error');
        }
    }

}


