<?php
namespace LazyRecord;
use LazyRecord\SchemaDeclare\Column;
use Exception;

abstract class SchemaDeclare
{

    const has_one = 1;
    const has_many = 2;
    const many_to_many = 3;

    public $relations = array();
    // public $accessors = array();
    public $columns = array();

    public $table;

    abstract function schema();


    function __construct()
    {

    }

    public function build()
    {
        $this->schema();
    }

    protected function table($table)
    {
        $this->table = $table;
        return $this;
    }

    public function getTable() 
    {
        return $this->table 
            ? $this->table 
            : $this->_classnameToTable();
    }

    public function getModelClass()
    {
        static $class;
        if( $class )
            return $class;

        if( -1 != ( $p = strrpos( $class = get_class($this) , 'Schema' ) ) ) {
            return $class = substr( $class , 0 , $p );
        }
        throw new Exception('Can not get model class from ' . $class );
    }


    protected function _classnameToTable() 
    {
        $class = $this->getModelClass();
        /**
         * If we got Yasumi\Model\UserModel, we have to strip. 
         */
        if( preg_match( '/(\w+?)(?:Model)?$/', $class ,$reg) ) {
            $table = @$reg[1];
            if( ! $table )
                throw new Exception( 'Table name error' );

            /* convert BlahBlah to blah_blah */
            $table =  strtolower( preg_replace( 
                '/(\B[A-Z])/e' , 
                "'_'.strtolower('$1')" , 
                $table ) );

            $inf = Inflector::getInstance();
            return $inf->pluralize( $table );
        } else { 
            throw new Exception('Table name convert error');
        }
    }

    protected function column($name)
    {
        if( isset($this->columns[$name]) ) {
            throw new Exception("column $name is already defined.");
        }
        return $this->columns[ $name ] = new Column( $name );
    }

    protected function hasOne($accessor,$selfColumn,$foreignClass,$foreignColumn = null)
    {
        // foreignColumn is default to foreignClass.primary key

        // $this->accessors[ $accessor ] = array( );
        $selfClass = $this->getModelClass();
        $this->relations[ $accessor ] = array(
            'type'           => self::has_one,
            'self'           => $selfColumn,
            'self_class'     => $selfClass,
            'foreign_class'  => $foreignClass,
            'foreign_column' => $foreignColumn,
        );
    }

    protected function hasMany($accessor,$foreignClass,$foreignColumn,$selfColumn)
    {
        $selfClass = $this->getModelClass();
        $this->relations[ $accessor ] = array(
            'type'           => self::has_many,
            'self'           => $selfColumn,
            'self_class'     => $selfClass,
            'foreign_class'  => $foreignClass,
            'foreign_column' => $foreignColumn,
        );
    }




}

