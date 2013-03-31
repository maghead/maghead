<?php
namespace LazyRecord\Schema;

class Relationship
    implements IteratorAggregate
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
        return new $modelClass;
    }

    public function newForeignCollection()
    {
        $schema = $this->newForeignSchema();
        $collectionClass = $schema->getCollectionClass();
        return new $collectionClass;
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
     * To support foreach operation.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->data);
    }


    /**
     * To support var_export
     */
    public static function __set_state($data)
    {
        return new self($data);
    }

}



