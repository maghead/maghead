<?php
use LazyRecord\SqlBuilder;

class PgsqlModelTest extends ModelTest
{
    public $driver = 'pgsql';
    public $schemaPath = 'tests/schema';
}

