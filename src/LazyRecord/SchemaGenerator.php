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

	protected function renderTemplate($file, $args)
	{
		$path = $this->getTemplatePath() . DIRECTORY_SEPARATOR . $file;
		ob_start();
		extract($args);
		require $path;
		$content = ob_get_contents();
		ob_clean();
		return $content;
	}


	public function generate()
	{
		$this->loadSchemaFiles();
		$classes = $this->getSchemaClasses();

		$this->logger->info( 'Found schema classes: ' . join(', ', $classes ) );
		foreach( $classes as $class ) {
			$this->logger->info( 'Building schema class: ' . $class );

			$schema = new $class;
			$schema->build();   /* initialize schema data */
			$schemaArray = $schema->export();

			$reflection = new \ReflectionObject( $schema );

			$source = $this->renderTemplate( 'Schema.php', array(
				'schema' => $schemaArray,
				'reflection' => $reflection,
		   	));

			$sourceFile = $this->targetPath . DIRECTORY_SEPARATOR 
				. str_replace( '\\' , DIRECTORY_SEPARATOR , $reflection->getNamespaceName() )
				. DIRECTORY_SEPARATOR
				. $schemaArray['model_class'] . 'SchemaProxy.php';

			file_put_contents( $sourceFile , $source );
		}

	}
}

