<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;
use RuntimeException;
use ReflectionObject;
use Traversable;
use RecursiveRegexIterator;
use RegexIterator;
use LazyRecord\ConfigLoader;
use ClassTemplate\TemplateClassFile;
use ClassTemplate\ClassFile;
use ClassTemplate\ClassInjection;
use CodeGen\ClassConst;

use LazyRecord\Schema;
use CLIFramework\Logger;

use LazyRecord\Schema\Factory\BaseModelClassFactory;
use LazyRecord\Schema\Factory\BaseCollectionClassFactory;
use LazyRecord\Schema\Factory\CollectionClassFactory;
use LazyRecord\Schema\Factory\ModelClassFactory;
use LazyRecord\Schema\Factory\SchemaProxyClassFactory;
use LazyRecord\Schema\DynamicSchemaDeclare;
use LazyRecord\Console;


/**
 * Builder for building static schema class file
 */
class SchemaGenerator
{
    public $config;

    public $forceUpdate = false;

    public $logger;

    public function __construct(ConfigLoader $config, Logger $logger)
    {
        $this->config = $config; // ConfigLoader::getInstance();
        $this->logger = $logger; // Console::getInstance()->getLogger();
    }

    public function setForceUpdate($force = true) 
    {
        $this->forceUpdate = $force;
    }

    public function getBaseModelClass() 
    {
        if ($this->config && $this->config->loaded) {
            return $this->config->getBaseModelClass();
        }
        return 'LazyRecord\BaseModel';
    }

    public function getBaseCollectionClass() {
        if ($this->config && $this->config->loaded) {
            return $this->config->getBaseCollectionClass();
        }
        return 'LazyRecord\BaseCollection';
    }


    /**
     * Returns code template directory
     */
    protected function getTemplateDirs()
    {
        static $templateDir;
        if ( $templateDir ) {
            return $templateDir;
        }
        $refl = new ReflectionObject($this);
        $path = $refl->getFilename();
        return $templateDir = dirname($refl->getFilename()) . DIRECTORY_SEPARATOR . 'Templates'; // should be LazyRecord/Schema/Templates
    }

    public function preventFileDir($path,$mode = 0755)
    {
        $dir = dirname($path);
        if ( ! file_exists($dir) ) {
            mkdir( $dir , $mode, true );
        }
    }

    /**
     * This method checks the exising schema file and the generated class file mtime.
     * If the schema file is newer or the forceUpdate flag is specified, then 
     * the generated class files should be updated.
     *
     * @param ClassTemplate\ClassFile $cTemplate
     * @param DeclareSchema $schema
     */
    protected function updateClassFile(ClassFile $cTemplate, DeclareSchema $schema, $overwrite = false)
    {
        // always update the proxy schema file
        $classFilePath = $schema->getRelatedClassPath( $cTemplate->getShortClassName() );

        // classes not Model/Collection class are overwriteable
        if (! file_exists($classFilePath)) {

            $this->writeClassTemplateToPath($cTemplate, $classFilePath, $overwrite);
            $this->logger->info2(" - Creating $classFilePath");
            return array( $cTemplate->getClassName(), $classFilePath );

        } else if ($schema->isNewerThanFile($classFilePath) || $this->forceUpdate || $overwrite ) {

            if ($this->writeClassTemplateToPath($cTemplate, $classFilePath, $overwrite)) {
                $this->logger->info2(" - Updating $classFilePath");
                return array( $cTemplate->getClassName() , $classFilePath );
            } else {
                $this->logger->info2(" - Skipping $classFilePath");
            }
        } else {
            $this->logger->info2(" - Skipping $classFilePath");
        }
    }

    /**
     * Generate modal class file, overwrite by default.
     *
     * @param Schema $schema
     * @param bool $force = true
     */
    public function generateModelClass(DeclareSchema $schema, $overwrite = false)
    {
        $cTemplate = ModelClassFactory::create($schema);
        return $this->updateClassFile($cTemplate, $schema, $overwrite); // do not overwrite
    }

    /**
     * Generate collection class from a schema object.
     *
     * @param DeclareSchema $schema
     * @return array class name, class file path
     */
    public function generateCollectionClass(DeclareSchema $schema, $overwrite = false)
    {
        $cTemplate = CollectionClassFactory::create($schema);
        return $this->updateClassFile($cTemplate, $schema, $overwrite);
    }


    /**
     * Write class template to the schema directory.
     *
     * @param string $directory The schema class directory.
     * @param ClassTemplate\ClassFile class template object.
     * @param boolean $overwrite Overwrite class file. 
     * @return array
     */
    public function writeClassTemplateToPath(ClassFile $cTemplate, $filepath, $overwrite = false) 
    {
        if (! file_exists($filepath) || $overwrite) {
            if (false === file_put_contents( $filepath, $cTemplate->render() )) {
                throw RuntimeException("Can not write file $filepath");
            }
            return true;
        } elseif ( file_exists($filepath) ) {
            return true;
        }
        return false;
    }




    public function generateSchema(SchemaInterface $schema, $overwrite = false)
    {
        $classMap = array();

        $cTemplates = array();
        $cTemplates[] = BaseModelClassFactory::create($schema, $this->getBaseModelClass());
        $cTemplates[] = SchemaProxyClassFactory::create($schema);
        $cTemplates[] = BaseCollectionClassFactory::create($schema, $this->getBaseCollectionClass());
        foreach ($cTemplates as $cTemplate) {
            if ($result = $this->updateClassFile($cTemplate, $schema, $overwrite)) {
                list($className, $classFile) = $result;
                $classMap[ $className ] = $classFile;
            }
        }

        if ($result = $this->generateCollectionClass($schema, false)) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }
        if ($result = $this->generateModelClass($schema, false)) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }
        return $classMap;
    }

    /**
     * Given a schema class list, generate schema files.
     *
     * @param array $classes class list or schema object list.
     * @return array class map array of schema class and file path.
     */
    public function generate(array $schemas, $overwrite = false)
    {
        // for generated class source code.
        $this->logger->debug("Setting up error handler...");
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            printf( "ERROR %s:%s  [%s] %s\n" , $errfile, $errline, $errno, $errstr );
        }, E_ERROR );

        // class map [ class => class file path ]
        $classMap = array();
        foreach( $schemas as $schema ) {
            $this->logger->debug("Checking " . get_class($schema) . '...');
            $generated = $this->generateSchema($schema, $overwrite);
            if (!empty($generated)) {
                foreach ($generated as $className => $classPath) {
                    $this->logger->info("- Updated " . $classPath);
                }
                $classMap += $generated;
            }
        }

        $this->logger->debug("Restoring error handler...");
        restore_error_handler();
        return $classMap;
    }
}

