<?php
namespace LazyRecord\Schema\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;

interface BuilderInterface
{
	public function build($schema);
}



