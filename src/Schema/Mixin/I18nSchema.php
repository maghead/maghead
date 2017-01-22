<?php

namespace Maghead\Schema\Mixin;

use Maghead\Schema\MixinDeclareSchema;

class I18nSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('lang')
            ->varchar(12)
            ->default('en');
    }
}
