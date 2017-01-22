<?php

namespace Maghead\Schema\Mixin;

use Maghead\Schema\MixinDeclareSchema;
use Maghead\Schema;
use DateTime;
use SQLBuilder\Raw;

class RevisionMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('revision_parent_id')
            ->int()
            ->unsigned()
            ->null()
            ;

        $this->column('revision_root_id')
            ->int()
            ->unsigned()
            ->null()
            ;

        $this->column('revision_created_at')
            ->timestamp()
            ->null()
            ->isa('DateTime')
            ->default(function () {
                return new \DateTime();
            });

        $this->column('revision_updated_at')
            ->timestamp()
            ->null()
            ->isa('DateTime')
            ->default(new Raw('CURRENT_TIMESTAMP'))
            ->onUpdate(new Raw('CURRENT_TIMESTAMP'))
            ;

        $this->belongsTo('root_revision', get_class($this->parentSchema), 'id', 'revision_root_id');
        $this->belongsTo('parent_revision', get_class($this->parentSchema), 'id', 'revision_parent_id');

        $this->addModelTrait('Maghead\\ModelTrait\\RevisionModelTrait');
    }
}
