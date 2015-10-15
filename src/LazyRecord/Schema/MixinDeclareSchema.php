<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\DeclareSchema;

use SQLBuilder\Universal\Query\CreateIndexQuery;

class MixinDeclareSchema extends DeclareSchema
{
    protected $parentSchema;

    public function __construct($parentSchema, array $options = array()) {
        $this->parentSchema = $parentSchema;
        parent::__construct($options);
    }


    public function getParentSchema() {
        return $this->parentSchema;
    }

    /**
     * Build schema
     */
    public function build(array $options = array())
    {
        $this->schema($options);
        // we don't need primary field (id) for mixin schema
    }

    /* is_a can not work on an abstract class */
    public function schema() { }

    public function postSchema() { }

    /**
     * compatible methods for BaseModel to mixin
     */
    public static function beforeCreate($args) { return $args; }
    public static function beforeUpdate($args) { return $args; }

    public static function afterCreate($args) {}
    public static function afterUpdate($args) {}


    public function index($name, array $columns = null)
    {
        if (isset($this->indexes[$name])) {
            return $this->indexes[$name];
        }
        $query = $this->indexes[$name] = new CreateIndexQuery($name);
        if ($columns) {
            if (empty($columns)) {
                throw new InvalidArgumentException("index columns must not be empty.");
            }
            $query->on($this->parentSchema->getTable(), $columns);
        }
        return $query;
    }


}
