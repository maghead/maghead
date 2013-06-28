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

    public function buildClassFilePath($directory, $className) 
    {
        return $directory . DIRECTORY_SEPARATOR . $className . '.php';
    }

    public function generateSchemaProxyClass($schema, $force = false)
    {
        $schemaProxyClass = $schema->getSchemaProxyClass();
        $cTemplate = new ClassTemplate( $schemaProxyClass, array( 
            'template_dirs' => $this->getTemplateDirs(),
            'template'      => 'Schema.php.twig',
        ));

        $classFilePath = $this->buildClassFilePath( $schema->getDirectory(), $cTemplate->getShortClassName() );
        if ( $schema->isNewerThanFile($classFilePath) || $force ) {
            $schemaClass = get_class($schema);
            $schemaArray = $schema->export();
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

            if ( $this->writeClassTemplateToPath($cTemplate, $classFilePath, true) ) {
                return array( $cTemplate->getClassName() => $classFilePath );
            }
        }
    }


    public function generateBaseModelClass($schema, $force = false)
    {
        $baseClass = $schema->getBaseModelClass();
        $cTemplate = new ClassTemplate( $baseClass, array( 
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $classFilePath = $this->buildClassFilePath( $schema->getDirectory(), $cTemplate->getShortClassName() );
        if ( $schema->isNewerThanFile($classFilePath) || $force ) {
            $cTemplate->addConsts(array(
                'schema_proxy_class' => ltrim($schema->getSchemaProxyClass(),'\\'),
                'collection_class' => ltrim($schema->getCollectionClass(),'\\'),
                'model_class' => ltrim($schema->getModelClass(),'\\'),
                'table' => $schema->getTable(),
            ));

            $cTemplate->addStaticVar( 'column_names',  $schema->getColumnNames() );
            $cTemplate->addStaticVar( 'column_hash',  array_fill_keys($schema->getColumnNames(), 1 ) );
            $cTemplate->addStaticVar( 'mixin_classes', array_reverse($schema->getMixinSchemaClasses()) );
            $cTemplate->extendClass( $this->getBaseModelClass() );

            if ( $this->writeClassTemplateToPath($cTemplate, $classFilePath, true) ) {
                return array( $cTemplate->getClassName() => $classFilePath );
            }
        }
    }



    /**
     * Generate modal class file, overwrite by default.
     *
     * @param Schema $schema
     * @param bool $force = true
     */
    public function generateModelClass($schema, $force = false)
    {
        $cTemplate = new ClassTemplate( $schema->getModelClass() , array(
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));

        $classFilePath = $this->buildClassFilePath($schema->getDirectory(), $cTemplate->getShortClassName());
        if ($schema->isNewerThanFile($classFilePath) || $force ) {
            $cTemplate->extendClass( $schema->getBaseModelClass() );
            if ( $this->writeClassTemplateToPath($cTemplate, $classFilePath, false) ) {
                return array( $cTemplate->getClassName() => $classFilePath );
            }
        }
    }

    public function generateBaseCollectionClass($schema)
    {
        $baseCollectionClass = $schema->getBaseCollectionClass();
        $cTemplate = new ClassTemplate( $baseCollectionClass, array(
            'template_dirs' => $this->getTemplateDirs(),
            'template' => 'Class.php.twig',
        ));
        $classFilePath = $this->buildClassFilePath($schema->getDirectory(), $cTemplate->getShortClassName());
        if ($schema->isNewerThanFile($classFilePath) ) {
            $cTemplate->addConst( 'schema_proxy_class' , '\\' . ltrim($schema->getSchemaProxyClass(),'\\') );
            $cTemplate->addConst( 'model_class' , '\\' . ltrim($schema->getModelClass(),'\\') );
            $cTemplate->addConst( 'table',  $schema->getTable() );
            $cTemplate->extendClass( 'LazyRecord\BaseCollection' );
            if ( $this->writeClassTemplateToPath($cTemplate, $classFilePath, true) ) {
                return array( $cTemplate->getClassName() => $classFilePath );
            }
        }
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
        $classFilePath = $this->buildClassFilePath($schema->getDirectory(), $cTemplate->getShortClassName());

        if ( $schema->isNewerThanFile( $classFilePath ) ) {
            $cTemplate->extendClass( $baseCollectionClass );
            if ( $this->writeClassTemplateToPath($cTemplate, $classFilePath) ) {
                return array( $cTemplate->getClassName() => $classFilePath );
            }
        }
    }


    /**
     * Write class template to the schema directory.
     *
     * @param string $directory The schema class directory.
     * @param LazyRecord\CodeGen\ClassTemplate class template object.
     * @param boolean $overwrite Overwrite class file. 
     * @return array
     */
    public function writeClassTemplateToPath($cTemplate, $filepath, $overwrite = false) 
    {
        if ( $overwrite ) {
            file_put_contents( $filepath, $cTemplate->render() );
            return true;
        } elseif ( file_exists($filepath) ) {
            return true;
        }
        return false;
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
            if ( $map = $this->generateSchemaProxyClass( $schema ) ) {
                $classMap = $classMap + $map;
            }

            // collection classes
            if ( $map = $this->generateBaseCollectionClass( $schema ) ) {
                $classMap = $classMap + $map;
            }
            if ( $map = $this->generateCollectionClass( $schema ) ) {
                $classMap = $classMap + $map;
            }

            // in new schema declare, we can describe a schema in a model class.
            if( $schema instanceof \LazyRecord\Schema\DynamicSchemaDeclare ) {
                if ( $map = $this->injectModelSchema($schema) ) {
                    $classMap = $classMap + $map;
                }
            } else {
                if ( $map = $this->generateBaseModelClass( $schema ) ) {
                    $classMap = $classMap + $map;
                }
                if ( $map = $this->generateModelClass( $schema ) ) {
                    $classMap = $classMap + $map;
                }
            }
        }

        restore_error_handler();
        return $classMap;
    }
}

