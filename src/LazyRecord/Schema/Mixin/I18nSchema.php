<?php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinSchemaDeclare;

class I18nSchema extends MixinSchemaDeclare
{
    function schema()
    {
        $this->column('lang')
            ->varchar(12)
            ->default('en');
    }
}
