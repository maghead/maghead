<?php
namespace Lazy\SchemaSqlBuilder;
use Lazy\Schema\SchemaDeclare;

interface DriverInterface
{
	public function build(SchemaDeclare $schema);
}



