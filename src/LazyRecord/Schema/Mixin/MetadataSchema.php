<?php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinDeclareSchema;
use LazyRecord\Schema;
use DateTime;

trigger_error("Deprecated class, please use MetadataMixinSchema instead.", E_USER_DEPRECATED);

class MetadataSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('updated_on')
            ->timestamp()
            ->default(function() { 
                return date('c'); 
            })
            ->timestamp();

        $this->column('created_on')
            ->timestamp()
            ->default(function() { 
                return date('c'); 
            })
            ->timestamp();
    }

    // Mixin methods
    public static function getAge($record) 
    {
        $createdOn = $record->created_on;
        $currentDate = new DateTime;
        return $currentDate->diff($createdOn);
    }


}
