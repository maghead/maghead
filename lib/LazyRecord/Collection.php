<?php
namespace LazyRecord;

use Exception,
    Iterator;

interface CollectionInterface 
{
    public function loadSQL( $sql );

    public function first();
    public function last();
    // public function next();
    public function random();
    public function get($index);
    public function count();
    public function size();

    /* delete current query stash */
    public function delete();


    /* update current query stash */
    public function update( $args );

    /* query sql and fetch objects */
    public function items();

    public function page( $per_page , $page_num );

    /**** Converters ****/
    /* return all data as a bit assocative array */
    public function toAssocArray();

    /* return all data as json string */
    public function toJSON();
}


class Collection extends \LazyRecord\SQLExecutor 
    implements CollectionInterface, Iterator
{
    public $modelClass;
    protected $cursor = 0;  /* current result position */
    protected $current_row; /* current data row object */
    protected $model;   /* cached model object */

    protected $cachedItems;
    protected $page;
    protected $perPage;
    protected $sqlBuilder;

    function __construct( $result = null )  
    {
        if( $result )
            $this->result = $result;

        if( ! $this->modelClass )
            throw new \Exception("Model class is not defined. " . __FILE__ . ":" . __LINE__ );

        $class = $this->modelClass;
        $this->initBuilder();
    }


    function getModel()
    {
        if( $this->model )
            return $this->model;

        $class = $this->modelClass;
        return $this->model = new $class;
    }


    function initBuilder() 
    {
        return $this->sqlBuilder = new \LazyRecord\SQLBuilder( $this->getModel() );
    }

    function __destruct() 
    {
        $this->freeResult();
    }



    /* dispatch to sql builder interface first */
    function __call( $name , $args ) {
        if( method_exists($this,$name) ) {
            return call_user_func_array( array($this,$name) , $args );
        }
        elseif( method_exists( $this->sqlBuilder , $name) ) {
            call_user_func_array( array($this->sqlBuilder,$name) , $args );
            return $this;
        }
        throw new \Exception( "Method $name not found." );
    }

    /* override sqlbuilder->reset */
    function reset() 
    {
        $this->initBuilder();
        $this->cachedItems = null;
        $this->freeResult();
        return $this;
    }

    public function page( $perPage , $page ) 
    {
        $this->page = $page;
        $this->perPage = $perPage;

        $start_limit = $perPage * ($page - 1);
        $limit = $perPage;
        $this->sqlBuilder->limit( $start_limit , $limit );
        return $this;
    }


    public function fromSQL( $sql ) 
    {
        // load data from SQL
        $this->result = $this->executeSQL( $sql );
        return $this;
    }

    public function random() 
    {
        $max = $this->count();
        $r = rand( $max , 1 );
        return $this->get( $r );
    }

    function get($pos) {
        $this->cursor = $pos;
        $this->result->data_seek( $this->cursor );
        $this->_updateCurrentRow();
        return $this->current();
    }

    function first() 
    {
        return $this->get(0);
    }

    function last() 
    {
        $cnt = $this->count();
        return $this->get( $cnt - 1 );
    }


    function size() {
        if( $this->result == null )
            throw new Exception("Empty Mysqli result.");
        return $this->result->num_rows;
    }

    function count()
    {
        return $this->size();
    }

    function update( $args ) 
    {
        $sql = $this->sqlBuilder->buildUpdateSQL( $args );
        return $this->executeSQL( $sql ); // return updated or failed
    }

    function delete( ) 
    {
        $sql = $this->sqlBuilder->buildDeleteSQL();
        return $this->executeSQL( $sql ); // return deleted or failed
    }

    function loadSQL( $sql ) 
    {
        $this->result = $this->executeSQL( $sql );
        return $this;
    }

    function refresh()
    {
        $this->close()->fetch();
        return $this;
    }

    function fetch() {
        $sql = $this->sqlBuilder->buildSelectSQL();
        $rs  = $this->executeSQL( $sql );
        $this->result = $rs;
        return $this;
    }

    function all() {
        $b = $this->initBuilder();
        $sql = $b->buildSelectSQL();
        $this->result = $this->executeSQL( $sql );
        return $this;
    }

    function items() 
    {
        if( $this->cachedItems )
            return $this->cachedItems;

        /* FIXME: if the result is defined ? */
        if( $this->result === null ) {
            $this->fetch();
        }
        return $this->cachedItems = $this->readItems();
    }

    private function readItems() {
        $rs = $this->result;
        $items = array();
        while( $data = $rs->fetch_assoc() ) {
            $class = $this->modelClass;
            $items[] = new $class( $data );
        }
        return $items;
    }

    /* XXX: this method duplicated with sql executor */
    function close() {
        if( $this->result ) {
            $this->result->close();
            $this->result = null;
        }
        return $this;
    }



    function pager( $pageNum = 1 , $perPage = 10 )
    {
        return new \LazyRecord\CollectionPager( $this , $pageNum , $perPage );
    }


    /****  Converters ****/
    function toAssocArray() {
        $rs = $this->result;
        $items = array();
        while( $row = $rs->fetch_assoc() ) {
            array_push( $items, $row );
        }
        return $items;
    }

    public function toJSON() {
        $array = $this->toAssocArray();
        return json_encode( $array );
    }

    /* 
    XXX: should we ? might be a security issue
         if so, put this method in accessor methods.
    */
    private function preventEmptyResult() 
    {
        if( $this->result === null )
            $this->fetch();
    }


    private function _updateCurrentRow()
    {
        $obj = $this->result->fetch_assoc();
        if( $obj ) {
            $class = new $this->modelClass;
            $this->current_row = new $class($obj);
        } else {
            $this->current_row = null;
        }
    }





    /**
     * Implements Iterator methods 
     *
     * */
    function rewind()
    { 
        $this->cursor = 0;
        $this->result->data_seek( 0 );
        $this->_updateCurrentRow();
    }

    /* is current row a valid row ? */
    function valid()
    {
        return $this->current_row;
    }

    function current() 
    { 
        return $this->current_row;
    }

    function next() 
    {
        ++$this->cursor;
        return $this->_updateCurrentRow();
    }

    function key()
    {
        return $this->current_row->id;
    }

}
