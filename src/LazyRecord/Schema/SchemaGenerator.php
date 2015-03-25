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
use ClassTemplate\TemplateClassDeclare;
use ClassTemplate\ClassConst;
use ClassTemplate\ClassInjection;
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
     * @param TemplateClassDeclare $cTemplate
     * @param DeclareSchema $schema
     */
    public function updateClassFile(TemplateClassDeclare $cTemplate, SchemaDeclare $schema, $overwrite = false) {
        // always update the proxy schema file
        $classFilePath = $schema->getRelatedClassPath( $cTemplate->getShortClassName() );

        // classes not Model/Collection class are overwriteable
        if (! file_exists($classFilePath)) {
            $this->writeClassTemplateToPath($cTemplate, $classFilePath, $overwrite);
            $this->logger->info2(" - Creating $classFilePath");
            return array( $cTemplate->getClassName(), $classFilePath );

        } elseif ( $schema->isNewerThanFile($classFilePath) || $this->forceUpdate ) {
            if ( $this->writeClassTemplateToPath($cTemplate, $classFilePath, $overwrite) ) {
                $this->logger->info2(" - Updating $classFilePath");
                return array( $cTemplate->getClassName() , $classFilePath );
            } else {
                $this->logger->info2(" - Skipping $classFilePath");
            }
        } else {
            $this->logger->info2(" - Skipping $classFilePath");
        }
    }


    public function generateSchemaProxyClass(SchemaDeclare $schema)
    {
        $cTemplate = SchemaProxyClassFactory::create($schema);
        return $this->updateClassFile($cTemplate, $schema, true);
    }

    public function generateBaseModelClass(SchemaDeclare $schema)
    {
        $cTemplate = BaseModelClassFactory::create($schema, $this->getBaseModelClass() );
        return $this->updateClassFile($cTemplate, $schema, true);
    }



    /**
     * Generate modal class file, overwrite by default.
     *
     * @param Schema $schema
     * @param bool $force = true
     */
    public function generateModelClass(SchemaDeclare $schema)
    {
        $cTemplate = ModelClassFactory::create($schema);
        return $this->updateClassFile($cTemplate, $schema, false); // do not overwrite
    }

    public function generateBaseCollectionClass(SchemaDeclare $schema)
    {
        $cTemplate = BaseCollectionClassFactory::create($schema, $this->getBaseCollectionClass() );
        return $this->updateClassFile($cTemplate, $schema, true);
    }


    /**
     * Generate collection class from a schema object.
     *
     * @param SchemaDeclare $schema
     * @return array class name, class file path
     */
    public function generateCollectionClass(SchemaDeclare $schema)
    {
        $cTemplate = CollectionClassFactory::create($schema);
        return $this->updateClassFile($cTemplate, $schema, false);
    }


    /**
     * Write class template to the schema directory.
     *
     * @param string $directory The schema class directory.
     * @param ClassTemplate\TemplateClassDeclare class template object.
     * @param boolean $overwrite Overwrite class file. 
     * @return array
     */
    public function writeClassTemplateToPath(TemplateClassDeclare $cTemplate, $filepath, $overwrite = false) 
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


    public function injectModelSchema(SchemaDeclare $schema)
    {
        $model = $schema->getModel();

        $injection = new ClassInjection($model);
        $injection->read();
        $injection->removeContent();
        $injection->appendContent( "\t" . new ClassConst('schema_proxy_class', ltrim($schema->getSchemaProxyClass() ,'\\') ) );
        $injection->appendContent( "\t" . new ClassConst('collection_class',   ltrim($schema->getCollectionClass() ,'\\') ) );
        $injection->appendContent( "\t" . new ClassConst('model_class',        ltrim($schema->getModelClass() ,'\\') ) );
        $injection->appendContent( "\t" . new ClassConst('table',              ltrim($schema->getTable() ,'\\') ) );
        $injection->write();
        $refl = new ReflectionObject($model);
        return array( $schema->getModelClass() => $refl->getFilename() );
    }


    public function generateSchema(SchemaInterface $schema)
    {
        $classMap = array();

        // support old-style schema declare
        if ( $result = $this->generateSchemaProxyClass( $schema) ) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }

        // collection classes
        if ( $result = $this->generateBaseCollectionClass( $schema ) ) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }
        if ( $result = $this->generateCollectionClass( $schema ) ) {
            list($className, $classFile) = $result;
            $classMap[ $className ] = $classFile;
        }

        // in new schema declare, we can describe a schema in a model class.
        if( $schema instanceof DynamicSchemaDeclare ) {
            if ( $result = $this->injectModelSchema($schema) ) {
                list($className, $classFile) = $result;
                $classMap[ $className ] = $classFile;
            }
        } else {
            if ( $result = $this->generateBaseModelClass($schema) ) {
                list($className, $classFile) = $result;
                $classMap[ $className ] = $classFile;
            }
            if ( $result = $this->generateModelClass( $schema ) ) {
                list($className, $classFile) = $result;
                $classMap[ $className ] = $classFile;
            }
        }
        return $classMap;
    }

    /**
     * Given a schema class list, generate schema files.
     *
     * @param array $classes class list or schema object list.
     * @return array class map array of schema class and file path.
     */
    public function generate(array $schemas)
    {
        // for generated class source code.
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            printf( "ERROR %s:%s  [%s] %s\n" , $errfile, $errline, $errno, $errstr );
        }, E_ERROR );

        // class map [ class => class file path ]
        $classMap = array();
        foreach( $schemas as $schema ) {
            $this->logger->info("Checking " . get_class($schema) . '...');
            $classMap += $this->generateSchema($schema);
        }

        restore_error_handler();
        return $classMap;
    }
}

