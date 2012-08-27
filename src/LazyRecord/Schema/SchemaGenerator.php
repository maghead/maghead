<?php
namespace LazyRecord\Schema;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;
use LazyRecord\ConfigLoader;
use Exception;
use LazyRecord\CodeGen\TemplateView;
use LazyRecord\CodeGen\ClassTemplate;

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

    public function getBaseModelClass() {
        if( $this->config && $this->config->loaded )
            return ltrim($this->config->getBaseModelClass(),'\\');
        return '\LazyRecord\BaseModel';
    }

    public function getBaseCollectionClass() {
        if( $this->config && $this->config->loaded )
            return ltrim($this->config->getBaseCollectionClass(),'\\');
        return '\LazyRecord\BaseCollection';
    }

    public function setLogger($logger)
    {
        $this->logger = $logger;
    }



    /**
     * Returns code template directory
     */
    protected function getTemplatePath()
    {
        $refl = new \ReflectionObject($this);
        $path = $refl->getFilename();
        return dirname($refl->getFilename()) . DIRECTORY_SEPARATOR . 'Templates';
    }

    protected function renderCode($file, $args)
    {
        $codegen = new TemplateView( $this->getTemplatePath() );
        $codegen->stash = $args;
        return $codegen->renderFile($file);
    }


    protected function generateClass($targetDir,$templateFile,$cTemplate,$extra = array(), $overwrite = false)
    {
        $source = $this->renderCode( $templateFile , array_merge( array(
            'class'   => $cTemplate,
        ), $extra ) );

        $sourceFile = $targetDir 
            . DIRECTORY_SEPARATOR 
            . $cTemplate->class->getName() . '.php';

        $class = ltrim($cTemplate->class->getFullName(),'\\');
        $this->logger->info( "Generating model class: $class => $sourceFile" );
        $this->preventFileDir( $sourceFile );

        if( $overwrite || ! file_exists( $sourceFile ) ) {
            if( file_put_contents( $sourceFile , $source ) === false ) {
                throw new Exception("$sourceFile write failed.");
            }
        }

        return array( $class, $sourceFile );
    }

    private function preventFileDir($path,$mode = 0755)
    {
        $dir = dirname($path);
        if( ! file_exists($dir) )
            mkdir( $dir , $mode, true );
    }

    protected function buildSchemaProxyClass($schema)
    {
        $schemaArray = $schema->export();
        $source = $this->renderCode( 'Schema.php.twig', array(
            // XXX: take off schema_data
            'schema_data' => $schemaArray,
            'schema' => $schema,
        ));

        $schemaClass = $schema->getClass();
        $modelClass  = $schema->getModelClass();
        $schemaProxyClass = $schema->getSchemaProxyClass();

        $cTemplate = new ClassTemplate( $schemaProxyClass );
        $cTemplate->addConst( 'schema_class' , '\\' . ltrim($schemaClass,'\\') );
        $cTemplate->addConst( 'model_class' , '\\' . ltrim($modelClass,'\\') );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->addConst( 'label',  $schema->getLabel() );

        /*
            return $this->generateClass( 'Class.php', $cTemplate );
         */


        /**
         * classname with namespace 
         */
        $schemaClass = $schema->getClass();
        $modelClass  = $schema->getModelClass();
        $schemaProxyClass = $schema->getSchemaProxyClass();


        $filename = explode( '\\' , $schemaProxyClass );
        $filename = (string) end($filename);
        $sourceFile = $schema->getDir() 
            . DIRECTORY_SEPARATOR . $filename . '.php';

        $this->preventFileDir( $sourceFile );

        if( file_exists($sourceFile) ) {
            $this->logger->info("$sourceFile found, overwriting.");
        }

        $this->logger->info( "Generating schema proxy $schemaProxyClass => $sourceFile" );
        file_put_contents( $sourceFile , $source );
        return array( $schemaProxyClass , $sourceFile );
    }


    protected function buildBaseModelClass($schema)
    {
        $baseClass = $schema->getBaseModelClass();

        // XXX: should export more information, so we don't need to get from schema. 
        $cTemplate = new ClassTemplate( $baseClass );
        $cTemplate->addConst( 'schema_proxy_class' , '\\' . ltrim($schema->getSchemaProxyClass(),'\\') );
        $cTemplate->addConst( 'collection_class' , '\\' . ltrim($schema->getCollectionClass(),'\\') );
        $cTemplate->addConst( 'model_class' , '\\' . ltrim($schema->getModelClass(),'\\') );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->extendClass( 
            $this->getBaseModelClass()
        );
        return $this->generateClass( $schema->getDir(), 'Class.php.twig', $cTemplate , array() , true );
    }

    protected function buildModelClass($schema)
    {
        $baseClass = $schema->getBaseModelClass();
        $modelClass = $schema->getModelClass();
        $cTemplate = new ClassTemplate( $schema->getModelClass() );
        $cTemplate->extendClass( $baseClass );
        return $this->generateClass( $schema->getDir() , 'Class.php.twig', $cTemplate );
    }

    protected function buildBaseCollectionClass($schema)
    {
        $baseCollectionClass = $schema->getBaseCollectionClass();

        $cTemplate = new ClassTemplate( $baseCollectionClass );
        $cTemplate->addConst( 'schema_proxy_class' , '\\' . ltrim($schema->getSchemaProxyClass(),'\\') );
        $cTemplate->addConst( 'model_class' , '\\' . ltrim($schema->getModelClass(),'\\') );
        $cTemplate->addConst( 'table',  $schema->getTable() );
        $cTemplate->extendClass( 'LazyRecord\\BaseCollection' );

        return $this->generateClass( $schema->getDir(), 'Class.php.twig', $cTemplate , array() , true ); // overwrite
    }

    protected function buildCollectionClass($schema)
    {
        $collectionClass = $schema->getCollectionClass();
        $baseCollectionClass = $schema->getBaseCollectionClass();

        $cTemplate = new ClassTemplate( $collectionClass );
        $cTemplate->extendClass( $baseCollectionClass );
        return $this->generateClass( $schema->getDir() , 'Class.php.twig', $cTemplate );
    }


    /*
    public function generateClass(\LazyRecord\Schema\SchemaDeclare $class) 
    {
        $schema = new $class; // it's a SchemaDeclare class.
    }
    */


    public function generate($classes)
    {
        // for generated class source code.
        set_error_handler(function($errno, $errstr, $errfile, $errline) {
            printf( "ERROR %s:%s  [%s] %s\n" , $errfile, $errline, $errno, $errstr );
        }, E_ERROR );

        /**
         * schema class mapping 
         */
        $classMap = array();

        $this->logger->info( 'Found schema classes: ' . join(', ', $classes ) );
        foreach( $classes as $class ) {
            $schema = new $class;

            $this->logger->debug( 'Building schema proxy class: ' . $class );
            list( $schemaProxyClass, $schemaProxyFile ) = $this->buildSchemaProxyClass( $schema );
            $classMap[ $schemaProxyClass ] = $schemaProxyFile;

            $this->logger->debug( 'Building base model class: ' . $class );
            list( $baseModelClass, $baseModelFile ) = $this->buildBaseModelClass( $schema );
            $classMap[ $baseModelClass ] = $baseModelFile;

            $this->logger->debug( 'Building model class: ' . $class );
            list( $modelClass, $modelFile ) = $this->buildModelClass( $schema );
            $classMap[ $modelClass ] = $modelFile;

            $this->logger->debug( 'Building base collection class: ' . $class );
            list( $c, $f ) = $this->buildBaseCollectionClass( $schema );
            $classMap[ $c ] = $f;

            $this->logger->debug( 'Building collection class: ' . $class );
            list( $c, $f ) = $this->buildCollectionClass( $schema );
            $classMap[ $c ] = $f;
        }

        restore_error_handler();
        return $classMap;
    }
}

