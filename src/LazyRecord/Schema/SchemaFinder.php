<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use ReflectionClass;
use RuntimeException;
use LazyRecord\ClassUtils;
use IteratorAggregate;


/**
 * find schema classes from files (or from current runtime)
 */
class SchemaFinder
    implements IteratorAggregate
{
    public $paths = array();

    public $classes = array();

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
        return ClassUtils::expand_schema_classes($classes);
    }

    public function getIterator() {
        return $this->getSchemaClasses();
    }
}

