<?php
namespace LazyRecord\Schema\Mixin;
use LazyRecord\Schema\MixinDeclareSchema;
use LazyRecord\Schema;
use DateTime;

class MetadataMixinSchema extends MixinDeclareSchema
{
    public function schema()
    {
        $this->column('updated_on')
            ->timestamp()
            ->default(function() { 
                return date('c'); 
            });

        $this->column('created_on')
            ->timestamp()
            ->default(function() { 
                return date('c'); 
            });
    }

    // Mixin methods
    public static function getAge($record) 
    {
        $createdOn = $record->created_on;
        $currentDate = new DateTime;
        return $currentDate->diff($createdOn);
    }


}
