<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use ReflectionClass;
use RuntimeException;


/**
 * find schema classes from files (or from current runtime)
 */
class SchemaFinder
{
    public $paths = array();

    public function in($path)
    {
        $this->paths[] = $path;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;
    }


    public function _loadSchemaFile($file) 
    {
        $code = file_get_contents($file);
        if( preg_match( '#' . preg_quote('SchemaDeclare') . '#xsm' , $code ) ) {
            require_once $file;
        }
    }

    public function loadFiles()
    {
        foreach( $this->paths as $path ) {
            if( is_file($path) ) {
                require_once $path;
            }
            else {
                // directory iterator
                $rdi   = new RecursiveDirectoryIterator($path);
                $rii   = new RecursiveIteratorIterator($rdi);
                $regex = new RegexIterator($rii, '/^.*\.php$/i', RecursiveRegexIterator::GET_MATCH);
                foreach( $regex as $k => $files ) {
                    foreach( $files as $file ) {
                        // make sure there schema class.
                        $this->_loadSchemaFile($file);
                    }
                }
            }
        }
    }

    public function getSchemaClasses()
    {
        $list = array();
        $classes = get_declared_classes();
        foreach( $classes as $class ) {
            $rf = new ReflectionClass( $class );

            // skip abstract classes.
            if( $rf->isAbstract() ) {
                continue;
            }

            if( is_a( $class, 'LazyRecord\Schema\MixinSchemaDeclare' ) 
                || $class == 'LazyRecord\Schema\MixinSchemaDeclare' 
                || is_subclass_of( $class, 'LazyRecord\Schema\MixinSchemaDeclare' ) ) 
            {
                continue;
            }

            if( is_subclass_of( $class, 'LazyRecord\Schema\SchemaDeclare' ) ) {
                $list[] = $class;
            }
        }

        $schemas = array();
        foreach( $list as $class ) {
            if( ! class_exists($class,true) ) {
                throw new RuntimeException("Schema class $class not found.");
            }

            $schema = new $class;
            $refs = $schema->getReferenceSchemas();
            foreach( $refs as $ref => $v )
                $schemas[] = $ref;
            $schemas[] = $class;
        }
        return array_unique($schemas);
    }

}

