<?php

namespace Maghead\Schema\Mixin;

use Maghead\Schema\MixinDeclareSchema;
use Maghead\Schema\DeclareSchema;
use DateTime;
use Magsql\Raw;

class MetadataMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('created_at')
            ->timestamp()
            ->null()
            ->isa('DateTime')
            ->default(function () {
                return new \DateTime();
            });

        $this->column('updated_at')
            ->timestamp()
            ->isa('DateTime')
            ->null()
            ->default(new Raw('CURRENT_TIMESTAMP'))
            ->onUpdate(new Raw('CURRENT_TIMESTAMP'))
            ;
    }

    // Mixin methods
    public static function getAge($record)
    {
        $createdOn = $record->getCreatedAt();
        $currentDate = new DateTime();
        return $currentDate->diff($createdOn);
    }
}
