<?php
namespace Lazy\Schema\SqlBuilder;
use Lazy\Schema\SchemaDeclare;

interface DriverInterface
{
	public function build(SchemaDeclare $schema, $rebuild = false );
}



