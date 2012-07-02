<?php
namespace LazyRecord\SqlBuilder;
use LazyRecord\Schema\SchemaDeclare;

interface BuilderInterface
{
	public function build($schema);
    public function buildIndex($schema);
}



