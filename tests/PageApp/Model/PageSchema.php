<?php
namespace PageApp\Model;
use LazyRecord\Schema\DeclareSchema;

class PageSchema extends DeclareSchema
{
    public function schema() {
        $this->column('title')->varchar(128)->localize(['en', 'fr']);
        $this->column('brief')->text();
        $this->mixin('RevisionMixinSchema');
        $this->mixin('LocalizeMixinSchema');
    }
}



