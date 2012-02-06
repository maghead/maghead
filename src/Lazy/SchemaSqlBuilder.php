<?php

namespace Lazy;

class SchemaSqlBuilder
{
	public $driver;
	public $type;

	function __construct($driverType)
	{
		$driverClass = get_class($this) . '\\' . ucfirst( $driverType ) . 'Driver';
		$this->driver = new $driverClass;
		$this->type = $driverType;
	}

	public function build(SchemaDeclare $schema)
	{
		$sql = $this->driver->build( $schema );
		return $sql;
	}

}




