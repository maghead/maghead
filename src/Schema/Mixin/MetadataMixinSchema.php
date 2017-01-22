<?php

namespace Maghead\Schema\Mixin;

use Maghead\Schema\MixinDeclareSchema;
use Maghead\Schema;
use DateTime;
use SQLBuilder\Raw;

class MetadataMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('created_on')
            ->timestamp()
            ->null()
            ->isa('DateTime')
            ->default(function () {
                return new \DateTime();
            });

        $this->column('updated_on')
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
        $createdOn = $record->getCreatedOn();
        $currentDate = new DateTime();
        return $currentDate->diff($createdOn);
    }
}
