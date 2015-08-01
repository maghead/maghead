<?php
namespace LazyRecord;
use Exception;
use RuntimeException;
use ReflectionClass;
use Doctrine\Common\Inflector\Inflector;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Exception\TableNameConversionException;

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
    static public function expand_schema_classes(array $classes)
    {
        $schemas = array();
        foreach ($classes as $class) {
            $schema = new $class; // declare schema
            $refs = $schema->getReferenceSchemas();
            foreach ( $refs as $refClass => $v ) {
                $schemas[] = $refClass;
            }
            $schemas[] = $class;
        }
        return self::schema_classes_to_objects(array_unique($schemas));
    }


    public static function schema_classes_to_objects(array $classes) 
    {
        $schemas = array();
        foreach ($classes as $class) {
            if (is_subclass_of($class,'LazyRecord\Schema\DeclareSchema',true)) {
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
    static public function filter_schema_classes(array $classes)
    {
        $list = array();
        foreach( $classes as $class ) {
            // skip abstract classes.
            if (
              ! is_subclass_of($class, 'LazyRecord\Schema\DeclareSchema',true)
              || is_a($class, 'LazyRecord\Schema\DynamicSchemaDeclare',true)
              || is_a($class, 'LazyRecord\Schema\MixinDeclareSchema',true)
              || is_a($class, 'LazyRecord\Schema\MixinSchemaDeclare',true)
              || is_subclass_of($class, 'LazyRecord\Schema\MixinDeclareSchema',true)
            ) { 
                continue; 
            }
            $rf = new ReflectionClass( $class );
            if ($rf->isAbstract()) {
                continue;
            }

            $list[] = $class;
        }
        return $list;
    }

    static public function convertClassToTableName($class)
    {
        if (preg_match( '/(\w+?)(?:Model)?$/', $class ,$reg)) {
            if (count($reg) < 2) {
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
            $table = Inflector::tableize($table);
            return Inflector::pluralize($table);
        } else {
            throw new TableNameConversionException("Table name convert error: $class", $class);
        }
    }

}


