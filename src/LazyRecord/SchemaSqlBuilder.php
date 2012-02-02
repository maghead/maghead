<?php

namespace LazyRecord;

class SchemaSqlBuilder
{
	public $driver;
	public $type;

	function __construct($driverType)
	{
		$driverClass = self . NS_SEPARATOR . ucfirst( $driverType ) . 'Driver';
		$this->driver = new $driverClass;
		$this->type = $driverType;
	}

	public function build($schema)
	{
		$this->driver->build( $schema );
	}

}




