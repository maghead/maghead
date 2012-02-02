<?php
namespace LazyRecord;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use RecursiveRegexIterator;
use RegexIterator;

/**
 * builder for building static schema class file
 */
class SchemaGenerator
{
	public $schemaPaths = array();

	public $targetPath;

	public $logger;

	public function __construct() {  

	}

	public function addPath( $path )
	{
		$this->schemaPaths[] = $path;
	}

	public function setLogger($logger)
	{
		$this->logger = $logger;
	}

	public function setTargetPath($path)
	{
		$this->targetPath = rtrim($path,DIRECTORY_SEPARATOR);
	}

	public function loadSchemaFiles()
	{
		foreach( $this->schemaPaths as $path ) {
			$rdi = new RecursiveDirectoryIterator($path);
			$rii = new RecursiveIteratorIterator($rdi);
			$regex = new RegexIterator($rii, '/^.+\.php$/i', RecursiveRegexIterator::GET_MATCH);
			foreach( $regex as $k => $files ) {
				foreach( $files as $file ) {
					$this->logger->info( "Loading file: $file" );
					require $file;
				}
			}
		}
	}

	protected function getSchemaClasses()
	{
		$schemaClasses = array();
		$classes = get_declared_classes();
		foreach( $classes as $class ) {
			if( is_subclass_of( $class , '\\LazyRecord\\SchemaDeclare' ) ) {
				$schemaClasses[] = $class;
			}
		}
		return $schemaClasses;
	}

	protected function getTemplatePath()
	{
		$refl = new \ReflectionObject($this);
		return dirname($refl->getFilename()) . DIRECTORY_SEPARATOR . 'Templates';
	}

	protected function renderCode($file, $args)
	{
		$codegen = new CodeGen( $this->getTemplatePath() );
		$codegen->stash = $args;
		return $codegen->renderFile($file);
	}

	private function preventFileDir($path,$mode = 0755)
	{
		$dir = dirname($path);
		if( ! file_exists($dir) )
			mkdir( $dir , $mode, true );
	}


	protected function tryRequire($file)
	{
		// try to require 
		try {
			require $file;
		} catch ( Exception $e ) {
			$this->logger->error( $e->getMessage() );
			throw $e;
		}
	}

	protected function buildSchemaProxyClass($schema)
	{
		$schemaArray = $schema->export();
		$source = $this->renderCode( 'Schema.php', array(
			'schema_data' => $schemaArray,
			'schema' => $schema,
		));

		/**
		* classname with namespace 
		*/
		$schemaClass = $schema->getClass();
		$modelClass  = $schema->getModelClass();
		$schemaProxyClass = $schema->getSchemaProxyClass();

		$sourceFile = $this->targetPath . DIRECTORY_SEPARATOR 
			. str_replace( '\\' , DIRECTORY_SEPARATOR , $schemaProxyClass ) . '.php';

		$this->preventFileDir( $sourceFile );

		if( file_exists($sourceFile) ) {
			$this->logger->info("$sourceFile found, overwriting.");
		}

		$this->logger->info( "Generating schema proxy $schemaProxyClass => $sourceFile" );
		file_put_contents( $sourceFile , $source );

		$this->tryRequire( $sourceFile );

		return array( $schemaProxyClass , $sourceFile );
	}

	protected function buildBaseModelClass($schema)
	{
		$baseClass = $schema->getBaseModelClass();
		$baseName  = explode('\\',$baseClass); $baseName = end($baseName);
		$namespace = $schema->getNamespace();

		$source = $this->renderCode( 'BaseModel.php', array(
			'namespace'  => $namespace,
			'base_class' => $baseClass,
			'base_name' => $baseName,
			'schema_proxy_class' => '\\'. $schema->getSchemaProxyClass(),
		));

		$sourceFile = $this->targetPath . DIRECTORY_SEPARATOR 
			. str_replace( '\\' , DIRECTORY_SEPARATOR , $baseClass ) . '.php';

		$this->logger->info( "Generating base model $baseClass => $sourceFile" );
		$this->preventFileDir( $sourceFile );

		file_put_contents( $sourceFile , $source );
		$this->tryRequire( $sourceFile );
		return array( $baseClass, $sourceFile );
	}

	protected function buildModelClass($schema)
	{
		$namespace = $schema->getNamespace();

		$baseClass = $schema->getBaseModelClass();
		$baseName  = explode('\\',$baseClass); $baseName = end($baseName);

		$modelClass = $schema->getModelClass();
		$modelName = explode('\\',$modelClass); $modelName = end($modelName);

		$source = $this->renderCode( 'BaseModel.php', array(
			'namespace'  => $namespace,
			'base_class' => $baseClass,
			'base_name' => $baseName,
			'schema_proxy_class' => '\\'. $schema->getSchemaProxyClass(),
		));

	}

	protected function buildBaseCollectionClass($schema)
	{
		$namespace = $schema->getNamespace();

		$baseCollectionClass = $schema->getBaseCollectionClass();
		$baseCOllectionName  = explode('\\',$baseCollectionClass); $baseName = end($baseName);

		$modelClass = $schema->getModelClass();
		$modelName = explode('\\',$modelClass); $modelName = end($modelName);

	}

	protected function buildCollectionClass($schema)
	{

	}

	public function generate()
	{
		$this->loadSchemaFiles();
		$classes = $this->getSchemaClasses();

		/**
		 * schema class mapping 
		 */
		$classMap = array();

		$this->logger->info( 'Found schema classes: ' . join(', ', $classes ) );
		foreach( $classes as $class ) {
			$schema = new $class;
			$schema->build();   /* initialize schema data */

			$this->logger->info( 'Building schema proxy class: ' . $class );
			list( $schemaProxyClass, $schemaProxyFile ) = $this->buildSchemaProxyClass( $schema );
			$classMap[ $schemaProxyClass ] = $schemaProxyFile;

			$this->logger->info( 'Building base model class: ' . $class );
			list( $baseModelClass, $baseModelFile ) = $this->buildBaseModelClass( $schema );
			$classMap[ $baseModelClass ] = $baseModelFile;
		}

	}
}

