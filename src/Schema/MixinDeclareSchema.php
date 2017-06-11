<?php

namespace Maghead\Schema;

abstract class MixinDeclareSchema extends DeclareSchema
{
    protected $parentSchema;

    public function __construct(DeclareSchema $parentSchema, array $options = array())
    {
        $this->parentSchema = $parentSchema;
        parent::__construct($options);
    }

    public function getParentSchema()
    {
        return $this->parentSchema;
    }

    /**
     * Return the current schema (for mixin schema, we need to return the parent schema object)
     */
    protected function getCurrentSchema()
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

    public function index($name, $columns = null, $using = null)
    {
        return $this->parentSchema->index($name, $columns, $using);
    }
}
