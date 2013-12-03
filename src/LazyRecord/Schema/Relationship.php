<?php
namespace LazyRecord\Schema;
use ArrayAccess;
use IteratorAggregate;

class Relationship
    implements IteratorAggregate, ArrayAccess
{

    public $data = array();

    public function __construct($data = array())
    {
        $this->data = $data;
    }

    public function newForeignSchema()
    {
        $class = $this->data['foreign_schema'];
        return new $class;
    }

    public function newForeignModel()
    {
        $schema = $this->newForeignSchema();
        $modelClass = $schema->getModelClass();
        return new $modelClass();
    }

    public function newForeignCollection()
    {
        $schema = $this->newForeignSchema();
        $collectionClass = $schema->getCollectionClass();
        return new $collectionClass;
    }


    public function isType($type) 
    {
        return $this->data['type'] === $type;
    }

    public function isManyToMany() 
    {
        return $this->data['type'] === SchemaDeclare::many_to_many;
    }

    public function isOneToMany() 
    {
        return $this->data['type'] === SchemaDeclare::has_many;
    }

    public function isHasMany() 
    {
        return $this->data['type'] === SchemaDeclare::has_many;
    }


    public function applyFilter(& $collection) {
        if ( isset($this->data['filter']) ) {
            $collection = call_user_func_array( $this->data['filter'] , $collection );
        }
    }

    public function applyWhere(& $collection) {
        if ( isset($this->data['where']) ) {
            return $collection->where($this->data['where']);
        }
    }

    public function applyOrder(& $collection) {
        if ( isset($this->data['order']) ) {
            foreach( $this->data['order'] as $o ) {
                $collection->order($o[0] , $o[1]);
            }
        }
    }

    public function getForeignCollection()
    {
        $collection = $this->newForeignCollection();
        $this->applyFilter($collection);
        $this->applyWhere($collection);
        $this->applyOrder($collection);
        return $collection;
    }

    /**
     * Provide dynamic cascading accessors
     *
     * $relationship->foreign_schema('something')
     * $relationship->view('something')
     */
    public function __call($m, $as)
    {
        $this->data[ $m ] = $as[0];
        return $this;
    }


    /**
     * Define filter for collection
     *
     * @param callback $filter filter callback.
     */
    public function filter($filter)
    {
        $this->data['filter'] = $filter;
        return $this;
    }



    /**
     * Save order on the relationship.
     *
     * @param string $column
     * @param string $ordering
     */
    public function order($column, $ordering)
    {
        if ( ! isset($this->data['order']) ) {
            $this->data['order'] = array();
        }
        $this->data['order'][] = array($column ,$ordering);
        return $this;
    }


    /**
     * Save where condition arguments for collection selection.
     *
     * @param array $args
     */
    public function where($args)
    {
        $this->data['where'] = $args;
        return $this;
    }



    /**
     * To support foreach operation.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }


    public function offsetSet($name,$value)
    {
        $this->data[$name] = $value;
    }

    public function offsetExists($name)
    {
        return isset($this->data[$name]);
    }

    public function offsetGet($name)
    {
        return $this->data[$name];
    }

    public function offsetUnset($name)
    {
        unset($this->data[$name]);
    }

    /**
     * To support var_export
     */
    public static function __set_state($data)
    {
        return new self($data['data']);
    }

}



