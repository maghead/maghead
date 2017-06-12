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
        // MySQL: ts TIMESTAMP
        // which equals to :
        // ts DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP
        $this->column('updated_at')
            ->timestamp()
            ->null()
            ->isa('DateTime')
            ->renderAs('DateTimeInput')
            ->default(new Raw('CURRENT_TIMESTAMP'))
            ->onUpdate(new Raw('CURRENT_TIMESTAMP'))
            ->label('Updated at')
            ;

        $this->column('created_at')
            ->timestamp()
            ->isa('DateTime')
            ->null()
            ->renderAs('DateTimeInput')
            ->label( _('建立時間') )
            ->default(function() {
                return new \DateTime;
            })
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
