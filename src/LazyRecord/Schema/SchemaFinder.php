<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use ReflectionClass;
use RuntimeException;
use IteratorAggregate;
use LazyRecord\ClassUtils;
use LazyRecord\ConfigLoader;

/**
 * Find schema classes from files (or from current runtime)
 *
 * 1. Find SchemaDeclare-based schema class files.
 * 2. Find model-based schema, pass dynamic schema class 
 */
class SchemaFinder
    implements IteratorAggregate
{

    public $paths = array();

    public $classes = array();

    public $config;

    public $logger;

    public function __construct()
    {
        $this->config = ConfigLoader::getInstance();
    }

    public function setLogger($logger) 
    {
        $this->logger = $logger;
    }

    public function in($path)
    {
        $this->paths[] = $path;
    }

    public function addPath($path)
    {
        $this->paths[] = $path;
    }


    // DEPRECATED
    public function loadFiles() { 
        return $this->find(); 
    }

    public function find()
    {
        if ( empty($this->paths) ) {
            return;
        }

        foreach( $this->paths as $path ) {
            if( is_file($path) ) {
                require_once $path;
            } else {
                $rii   = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path,
                        RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ( $rii as $fi ) {
                    if ( substr($fi->getFilename(), -10) == "Schema.php" ) {
                        require_once $fi->getPathname();
                    }
                }
            }
        }
    }


    /**
     * This method is deprecated.
     */
    public function getSchemaClasses() 
    {
        return $this->getSchemas();
    }


    /**
     * Returns schema objects
     *
     * @return array Schema objects
     */
    public function getSchemas()
    {
        $classes = ClassUtils::get_declared_schema_classes();
        return ClassUtils::expand_schema_classes($classes);
    }

    public function getIterator() 
    {
        return $this->getSchemas();
    }
}

