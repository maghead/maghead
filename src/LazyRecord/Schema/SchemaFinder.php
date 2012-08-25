<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use ReflectionClass;
use RuntimeException;
use LazyRecord\ClassUtils;


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
        $classes = ClassUtils::get_declared_schema_classes();

        // get referenced schema classes and sort it.
        $schemas = array();
        foreach( $classes as $class ) {
            $schema = new $class; // declare schema
            $refs = $schema->getReferenceSchemas();
            foreach( $refs as $ref => $v )
                $schemas[] = $ref;
            $schemas[] = $class;
        }
        return array_unique($schemas);
    }

}

