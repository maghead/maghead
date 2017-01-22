<?php

namespace Maghead\Schema;

class MixinDeclareSchema extends DeclareSchema
{
    protected $parentSchema;

    public function __construct($parentSchema, array $options = array())
    {
        $this->parentSchema = $parentSchema;
        parent::__construct($options);
    }

    public function getParentSchema()
    {
        return $this->parentSchema;
    }

    /**
     * Build schema.
     */
    public function build(array $options = array())
    {
        $this->schema($options);
        // we don't need primary field (id) for mixin schema
    }

    /* is_a can not work on an abstract class */
    public function schema()
    {
    }

    public function postSchema()
    {
    }

    /**
     * compatible methods for BaseModel to mixin.
     */
    public static function beforeCreate($args)
    {
        return $args;
    }
    public static function beforeUpdate($args)
    {
        return $args;
    }

    public static function afterCreate($args)
    {
    }
    public static function afterUpdate($args)
    {
    }

    public function index($name, $columns = null, $using = null)
    {
        return $this->parentSchema->index($name, $columns, $using);
    }
}
