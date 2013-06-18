<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Exception;
use ReflectionObject;
use RecursiveRegexIterator;
use RegexIterator;
use LazyRecord\ConfigLoader;
use LazyRecord\CodeGen\ClassTemplate;
use LazyRecord\CodeGen\ClassConst;
use LazyRecord\CodeGen\ClassInjection;
use LazyRecord\Schema;


/**
 * Builder for building static schema class file
 */
class SchemaGenerator
{
    public $logger;

    public $config;

    public function __construct() 
    {
        $this->config = ConfigLoader::getInstance();
    }

    public function getBaseModelClass() 
    {
        if ( $this->config && $this->config->loaded ) {
            return ltrim($this->config->getBaseModelClass(),'\\');
        }
        return 'LazyRecord\BaseModel';
    }

    public function getBaseCollectionClass() {
        if( $this->config && $this->config->loaded )
            return ltrim($this->config->getBaseCollectionClass(),'\\');
        return 'LazyRecord\BaseCollection';
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
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
        return $templateDir = dirname($refl->getFilename()) . DIRECTORY_SEPARATOR . 'Templates';
    }

    public function preventFileDir($path,$mode = 0755)
    {
        $dir = dirname($path);
        if ( ! file_exists($dir) ) {
            mkdir( $dir , $mode, true );
        }
    }

    public function generateSchemaProxyClass($schema)
    {
        $schemaArray = $schema->export();
        $schemaClass = get_class($schema);
        $modelClass  = $schema->getModelClass();
        $schemaProxyClass = $schema->getSchemaProxyClass();
        $cTemplate = new ClassTemplate( $schemaProxyClass, array( 
            'template_dirs' => $this->getTemplateDirs(),
            'template'      => 'Schema.php.twig',
        ));

        $cTemplate->addConst( 'schema_class'     , ltrim($schemaClass,'\\') );
        $cTemplate->addConst( 'collection_class' , $schemaArray['collection_class'] );
        $cTemplate->addConst( 'model_class'      , $schemaArray['model_class'] );
        $cTemplate->addConst( 'model_name'       , $schema->getModelName() );
        $cTemplate->addConst( 'model_namespace'  , $schema->getNamespace() );
        $cTemplate->addConst( 'primary_key'      , $schemaArray['primary_key'] );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->addConst( 'label',  $schema->getLabel() );

        // export column names excluding virtual columns
        $cTemplate->addStaticVar( 'column_names',  $schema->getColumnNames() );
        $cTemplate->addStaticVar( 'column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
        $cTemplate->addStaticVar( 'mixin_classes',  array_reverse($schema->getMixinSchemaClasses()) );

        // export column names including virutal columns
        $cTemplate->addStaticVar( 'column_names_include_virtual',  $schema->getColumnNames(true) );

        $cTemplate->schema = $schema;
        $cTemplate->schema_data = $schemaArray;
        return $this->writeClassTemplateToDirectory($schema->getDirectory(), $cTemplate, true);
    }


    public function generateBaseModelClass($schema)
    {
        $baseClass = $schema->getBaseModelClass();
        $cTemplate = new ClassTemplate( $baseClass, array( 
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->addConst( 'schema_proxy_class' , ltrim($schema->getSchemaProxyClass(),'\\') );
        $cTemplate->addConst( 'collection_class' , ltrim($schema->getCollectionClass(),'\\') );
        $cTemplate->addConst( 'model_class' , ltrim($schema->getModelClass(),'\\') );
        $cTemplate->addConst( 'table',  $schema->getTable() );

        $cTemplate->addStaticVar( 'column_names',  $schema->getColumnNames() );
        $cTemplate->addStaticVar( 'column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
        $cTemplate->addStaticVar( 'mixin_classes', array_reverse($schema->getMixinSchemaClasses()) );

        $cTemplate->extendClass( $this->getBaseModelClass() );

        // overwrite
        return $this->writeClassTemplateToDirectory($schema->getDirectory(), $cTemplate, true);
    }

    public function generateModelClass($schema)
    {
        $class = $schema->getModelClass();
        $cTemplate = new ClassTemplate( $schema->getModelClass() , array(
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( $schema->getBaseModelClass() );
        return $this->writeClassTemplateToDirectory($schema->getDirectory(), $cTemplate);
    }

    public function generateBaseCollectionClass($schema)
    {
        $baseCollectionClass = $schema->getBaseCollectionClass();
        $cTemplate = new ClassTemplate( $baseCollectionClass, array(
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->addConst( 'schema_proxy_class' , '\\' . ltrim($schema->getSchemaProxyClass(),'\\') );
        $cTemplate->addConst( 'model_class' , '\\' . ltrim($schema->getModelClass(),'\\') );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->extendClass( 'LazyRecord\BaseCollection' );

        // we should overwrite the base collection class.
        return $this->writeClassTemplateToDirectory($schema->getDirectory(), $cTemplate, true);
    }


    /**
     * Generate collection class from a schema object.
     *
     * @param SchemaDeclare $schema
     * @return array class name, class file path
     */
    public function generateCollectionClass(SchemaDeclare $schema)
    {
        $collectionClass = $schema->getCollectionClass();
        $baseCollectionClass = $schema->getBaseCollectionClass();
        $cTemplate = new ClassTemplate( $collectionClass, array(
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $cTemplate->extendClass( $baseCollectionClass );

        return $this->writeClassTemplateToDirectory($schema->getDirectory(), $cTemplate);
    }


    /**
     * Write class template to the schema directory.
     *
     * @param string $directory The schema class directory.
     * @param LazyRecord\CodeGen\ClassTemplate class template object.
     * @param boolean $overwrite Overwrite class file. 
     * @return array
     */
    public function writeClassTemplateToDirectory($directory,$cTemplate,$overwrite = false)
    {
        $sourceCode = $cTemplate->render();
        $classFile = $this->writeClassToDirectory($directory, $cTemplate->class->getName(),$sourceCode, $overwrite);
        return array( $cTemplate->class->getFullName() => $classFile );
    }

    /**
     * Write class code to a directory with class name
     *
     * @param path $directory
     * @param string $className
     * @param string $sourceCode
     * @param boolean $overwrite
     */
    public function writeClassToDirectory($directory,$className,$sourceCode, $overwrite = false)
    {
        // get schema dir
        $filePath = $directory . DIRECTORY_SEPARATOR . $className . '.php';
        $this->preventFileDir( $filePath );
        if( $overwrite || ! file_exists( $filePath ) ) {
            if( file_put_contents( $filePath , $sourceCode ) === false ) {
                throw new Exception("$filePath write failed.");
            }
        }
        return $filePath;
    }


    public function injectModelSchema($schema)
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

    /**
     * Given a schema class list, generate schema files.
     *
     * @param array $classes class list or schema object list.
     * @return array class map array of schema class and file path.
     */
    public function generate($schemas)
    {
        // for generated class source code.
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            printf( "ERROR %s:%s  [%s] %s\n" , $errfile, $errline, $errno, $errstr );
        }, E_ERROR );

        // class map [ class => class file path ]
        $classMap = array();
        foreach( (array) $schemas as $schema ) {

            // support old-style schema declare
            $map = $this->generateSchemaProxyClass( $schema );
            $classMap = $classMap + $map;

            // collection classes
            $map = $this->generateBaseCollectionClass( $schema );
            $classMap = $classMap + $map;
            $map = $this->generateCollectionClass( $schema );
            $classMap = $classMap + $map;

            // in new schema declare, we can describe a schema in a model class.
            if( $schema instanceof \LazyRecord\Schema\DynamicSchemaDeclare ) {
                $map  = $this->injectModelSchema($schema);
                $classMap = $classMap + $map;

            } else {
                $map = $this->generateBaseModelClass( $schema );
                $classMap = $classMap + $map;

                $map = $this->generateModelClass( $schema );
                $classMap = $classMap + $map;

            }
        }

        restore_error_handler();
        return $classMap;
    }
}

