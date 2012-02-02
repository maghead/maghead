<?php

namespace LazyRecord\SchemaSqlBuilder;

interface DriverInterface
{
	public function build(SchemaDeclare $schema);
}



