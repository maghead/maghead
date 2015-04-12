<?php
namespace LazyRecord\Schema;

abstract class TemplateSchema extends DeclareSchema
{
    abstract public function yieldSchemas();
}


