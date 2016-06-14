<?php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinDeclareSchema;
use LazyRecord\Schema;
use DateTime;

class RevisionMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('revision_parent_id')
            ->int()
            ->null()
            ;

        $this->column('revision_root_id')
            ->int()
            ->null()
            ;

        $this->column('revision_updated_at')
            ->datetime()
            ->isa('DateTime')
            ->default(function() { 
                return new \DateTime;
            });

        $this->column('revision_created_at')
            ->datetime()
            ->isa('DateTime')
            ->default(function() { 
                return new \DateTime;
            });

        $this->belongsTo('root_revision', get_class($this->parentSchema), 'id', 'revision_root_id');
        $this->belongsTo('parent_revision', get_class($this->parentSchema), 'id', 'revision_parent_id');

        $this->addModelTrait('LazyRecord\\ModelTrait\\RevisionModelTrait');
    }
}


