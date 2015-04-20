<?php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinSchemaDeclare;
use LazyRecord\Schema;
use DateTime;

class RevisionMixinSchema extends MixinSchemaDeclare
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
            ->default(function() { 
                return date('c'); 
            })
            ->timestamp();

        $this->column('revision_created_at')
            ->datetime()
            ->default(function() { 
                return date('c'); 
            });

        $this->addModelTrait('RevisionModelTrait');
    }
}


