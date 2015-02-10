<?php
namespace LazyRecord\Schema;
use ArrayAccess;
use IteratorAggregate;
use SQLBuilder\Universal\Syntax\Conditions;

class Relationship implements IteratorAggregate, ArrayAccess
{
    const HAS_MANY = 1;
    const HAS_ONE = 2;
    const BELONGS_TO = 3;
    const MANY_TO_MANY = 4;

    public $data = array();

    public $where;

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


    /**
     * Resolve the junction relationship to retrieve foreign collection of the foreign collection.
     *
     * This method is only for many-to-many relationship object.
     *
     * @return LazyRecord\BaseCollection The foreign foreign collection.
     */
    public function newForeignForeignCollection($junctionRelation)
    {
        $junctionSchema  = new $junctionRelation['foreign_schema'];
        $foreignRelation = $junctionSchema->getRelation( $this['relation_foreign'] );
        $collection = $foreignRelation->newForeignCollection();
        $this->applyFilter($collection); // apply this filter to the foreign collection.
        return $collection;
    }

    public function isType($type) 
    {
        return $this->data['type'] === $type;
    }

    public function isManyToMany() 
    {
        return $this->data['type'] === Relationship::MANY_TO_MANY;
    }

    public function isOneToMany() 
    {
        return $this->data['type'] === Relationship::HAS_MANY;
    }

    public function isHasMany() 
    {
        return $this->data['type'] === Relationship::HAS_MANY;
    }


    public function applyFilter(& $collection) 
    {
        if ( isset($this->data['filter']) ) {
            $collection = call_user_func_array( $this->data['filter'] , array($collection) );
        }
    }

    public function applyWhere(& $collection) 
    {
        if ($this->where) {
            $collection->setWhere($this->where);
        }
    }

    public function applyOrder(& $collection) 
    {
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
    public function orderBy($column, $ordering)
    {
        if (!isset($this->data['order']) ) {
            $this->data['order'] = array();
        }
        $this->data['order'][] = array($column ,$ordering);
        return $this;
    }


    public function where($expr = NULL , array $args = array()) {
        if (!$this->where) {
            $this->where = new Conditions;
        }
        if ($expr) {
            if (is_string($expr)) {
                $this->where->appendExpr($expr, $args);
            } else if (is_array($expr)) {
                foreach($expr as $key => $val) {
                    $this->where->equal($key, $val);
                }
            } else {
                throw new InvalidArgumentException("Unsupported argument type of 'where' method.");
            }
        }
        return $this->where;
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
    public static function __set_state(array $data)
    {
        return new self($data['data']);
    }


    public function __get($key)
    {
        if (isset($this->data[$key])) {
            return $this->data[$key];
        }
    }

    public function __set($key, $val)
    {
        $this->data[$key] = $val;
    }

}



