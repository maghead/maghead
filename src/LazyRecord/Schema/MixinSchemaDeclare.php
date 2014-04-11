<?php
namespace LazyRecord\Schema;

class MixinSchemaDeclare extends SchemaDeclare
{
    /**
     * Build schema
     */
    public function build( $options = array() )
    {
        $this->schema( $options );
        // we don't need primary field (id) for mixin schema
    }

    /* is_a can not work on an abstract class */
    public function schema() { }


    /**
     * compatible methods for BaseModel to mixin
     */
    public static function beforeCreate($args) { return $args; }
    public static function beforeUpdate($args) { return $args; }

    public static function afterCreate($args) {}
    public static function afterUpdate($args) {}
}
