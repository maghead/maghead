<?php
namespace Lazy\SchemaSqlBuilder;
use Lazy\SchemaDeclare;

interface DriverInterface
{
	public function build(SchemaDeclare $schema);
}



