<?php
namespace LazyRecord\Schema\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;

interface DriverInterface
{
	public function build(SchemaDeclare $schema, $rebuild = false );
}



