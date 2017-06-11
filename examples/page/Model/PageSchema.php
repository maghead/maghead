<?php
namespace PageApp\Model;

use Maghead\Schema\DeclareSchema;
use Maghead\Extensions\Revision\RevisionMixinSchema;
use Maghead\Extensions\Localize\LocalizeMixinSchema;

class PageSchema extends DeclareSchema
{
    public function schema()
    {
        $this->column('title')->varchar(128)->localize(['en', 'fr']);
        $this->column('brief')->text();
        $this->mixin(RevisionMixinSchema::class);
        $this->mixin(LocalizeMixinSchema::class);
    }
}
