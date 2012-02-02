<?php
namespace LazyRecord\SchemaSqlBuilder;
use LazyRecord\SchemaDeclare;

interface DriverInterface
{
	public function build(SchemaDeclare $schema);
}



