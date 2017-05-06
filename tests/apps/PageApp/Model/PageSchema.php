<?php
namespace PageApp\Model;

use Maghead\Schema\DeclareSchema;

class PageSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('title')->varchar(128)->localize(['en', 'fr']);
        $this->column('brief')->text();
        $this->mixin('Maghead\\Extensions\\Revision\\RevisionMixinSchema');
        $this->mixin('Maghead\\Extensions\\Localize\\LocalizeMixinSchema');
    }
}
