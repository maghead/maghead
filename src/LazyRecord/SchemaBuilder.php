<?php

namespace LazyRecord;

/**
 * builder for building static schema 
 *
 */
class SchemaBuilder 
{
	public $schemaPaths = array();

	public $targetPath;

	public function __construct() {  

	}

	public function addPath( $path )
	{
		$this->schemaPaths[] = $path;
	}

	public function setTargetPath($path)
	{
		$this->targetPath = $path;
	}
}




