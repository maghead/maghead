<?php

/*
- LazyRecordDatabaseHandle
    - SQLBuilder 
        - LazyRecordSQLExecutor
            - ModelBase
            - Collection

*/
namespace LazyRecord;

interface SQLBuilderInterface 
{
    public function distinct( $col );
    public function select( $cols );
    public function addSelect( $sel );
    public function aggStart( $agg );
    public function aggEnd();
    public function join( $selfColumn , $alias , $joinColumn );
    public function order( $column , $desc );
    public function group( $column );
    public function reset();

    public function where( $params ); // the same as load_by_cols
    public function limit( $limit , $count );  // shouldn't be accessable for model.
    // public function distinct();
}

class SQLBuilder {

    public $model;
    public $modelClass;

    // main table alias
    protected $mainAlias = 'm';

    // condition array
    protected $where = array(); // query where (condition)

    protected $limit;
    protected $limitFrom;

    protected $select = array("m.*");

    /*
    join property:
            [ 
                "model" => $m,
                "table" => $m->getTable(),
                "alias" => $alias,
                "self"  => $selfColumn,
                "join"  => $joinColumn 
            ],...
     */
    protected $join = array();
    protected $order = array();
    protected $group = array();

    function __construct( $model ) {
        $this->setModel( $model );
    }

    public function findJoinColumn( $column ) {
        foreach( $this->join as $j ) {
            if( $j["self"] == $column )
                return $j;
        }
    }

    public function findJoinAlias( $alias ) {
        foreach( $this->join as $j ) {
            if( $j["alias"] == $alias )
                return $j;
        }
    }

    public function setMainAlias( $alias ) {
        $this->mainAlias = $alias;
    }

    public function reset() {
        $this->join = array();
        $this->order = array();
        $this->group = array();
        $this->where = array();
        $this->select = array('m.*');
        $this->mainAlias = 'm';
    }

    public function setModel( $model ) {
        $this->model = $model;
        $this->modelClass = get_class($model);
    }

    public function inflateValue( $value ) {
        // default value inflator
        if( is_string( $value ) ) {
            return array( "'%s'" , mysql_escape_string( $value ) );
        }
        elseif( is_float( $value ) ) {
            return array( "%f" , $value );
        }
        elseif( is_int( $value ) ) {
            return array( "%d",  $value );
        }
        elseif( is_bool( $value ) ) {
            return array( "%s",  $value ? "TRUE" : "FALSE" );
        }

        // XXX: should depends on database connection.
        return array( "'%s'" , mysql_escape_string($value) );
    }

    public function aggStart( $agg = "AND" ) {
        array_push( $this->where , array( "agg_start" => $agg ) );
    }

    public function aggEnd() {
        array_push( $this->where , array( "agg_end" => 1 ) );
    }


    /* Abstract SQL Interface */
    public function select( $cols ) {
        $this->select = is_array( $cols ) ? $cols : array( $cols );
    }

    public function distinct( $col ) {
        $this->select = array( "distinct " . $col );
    }

    public function addSelect( $col ) {
        array_push( $this->select , $col );
    }

    public function where( $params , $agg_type = "AND" ) 
    {
        if( is_array($params) ) {
            if( count($this->where) == 0 )
                $this->where[] = array( "params" => $params );
            else {
                $end = end($this->where);
                if( @$end["agg_start"] )
                    $this->where[] = array( "params" => $params );
                else
                    $this->where[] = array( "params" => $params , "agg" => $agg_type );
            }
        } elseif( is_string($params ) ) {

            if( count($this->where) == 0 )
                $this->where[] = array( "statement" => $params );
            else {
                $end = end($this->where);
                if( @$end["agg_start"] )
                    $this->where[] = array( "statement" => $params );
                else
                    $this->where[] = array( "statmenet" => $params , "agg" => $agg_type );
            }
        } else {
            throw new Exception('Unsupported type for "where" method');
        }
    }

    public function join( $selfColumn , $alias = null , $join_column = 'id' ) {

        /* automatically generate an alias id */
        if( $alias == null )
            $alias = 'a' . count($this->join);

        /* get refer model class and init model object to get columns. */
        $refer_model = $this->model->getColumn($selfColumn)->refer;

        $m = new $refer_model;
        $columns = $m->getColumnNames();

        foreach( $columns as $name ) {
            $as = $alias . '_' . $name;
            array_push( $this->select , " $alias.$name as $as " );
        }

        $this->join[ $selfColumn ] = array(
            "model" => $m,
            "table" => $m->getTable(),
            "alias" => $alias,
            "self"  => $selfColumn,
            "join"  => $join_column
        );
    }

    public function group( $column ) {
        array_push( $this->group , $column );
    }

    public function order( $column , $ordering = 'desc' ) {
        array_push( $this->order , array(
            "column" => $column,
            "ordering" => $ordering
        ));
		return $this;
    }

    public function limit($arg1,$arg2 = null) {
        if( $arg2 ) {
            $this->limit  = $arg1;
            $this->limitFrom = $arg2;
        }
        $this->limit = $arg1;
    }




    /* SQL Generator */
    protected function getSelectClause() {
        return sprintf("SELECT %s FROM %s %s"
            , join(',', $this->select) 
            , $this->model->getTable()
            , $this->mainAlias );
    }

    protected function getDeleteClause() {
        return sprintf("DELETE FROM %s"
            , $this->model->getTable() );
    }

    protected function getInsertClause( $args ) {
        // XXX: should use exception here instead of die.
        $model = $this->model;
        $args = $model->deflateArgs( $args );

        // go get column attributes and generate sql fields and values
        $stm_columns = array();
        $stm_fields  = array();
        $stm_values  = array();

        foreach( $model->columns as $name => $column ) {
            $value     = @$args[ $name ];
            $field     = null;
            $stm_value = null; // inflate value

            $has_key    = array_key_exists( $name , $args );
            $force_null = $has_key && $value === null;

            if( $name == "id" && $value === null )
                continue;

            /* 
            if value is null in column, 
                try to get default value 
                if no default value
                    check if force null
                        insert null
                    or
                        skip column
             * */
            if( $value === null ) {
                $default = $column->getDefaultValue();

                if( $default ) {
                    $stm_value = $column->inflateValue( $default );
                    $field     = $column->getSprintfField( $default );
                } 
                elseif( $column->defaultSqlValue ) {
                    $stm_value = $column->defaultSqlValue;
                    $field     = "%s";
                }
                elseif( $force_null ) {
                    $stm_value = "NULL";
                    $field     = "%s";
                }
                else 
                    continue;
            } else {
                // if we got value
                if( $has_key ) {
                    $stm_value = $column->inflateValue( $value );
                    $field     = $column->getSprintfField( $value );
                } else continue;
            }

            array_push( $stm_columns , $name );
            array_push( $stm_fields  , $field );
            array_push( $stm_values  , $stm_value );
        }

        $sql = "INSERT INTO {$model->getTable()} ( " 
            . join( ',' , $stm_columns )
            . " ) VALUES ( " . join( ',' , $stm_fields ) . ' );';

        # $model->logger->write( $sql );
        array_unshift( $stm_values , $sql );

        $sql = call_user_func_array( 'sprintf' , $stm_values );

        # var_dump( $sql );
        # $model->logger->write( $sql );

        return $sql;
    }

    protected function getUpdateSQL( $args ) {
        $model = $this->model;
        $args = $model->deflateArgs( $args );

        $stm_columns = array();
        $stm_parts   = array();
        $stm_values  = array();

        foreach( $args as $k => $v ) {
            $c = $model->getColumn( $k );
            if( ! $c )
                continue;

            if( $c->immutable ) {
                throw new Exception( "Column is immutable." );
                continue;
            }

            $sprintf = null;
            $inflate = null;

            if( $v === null ) {
                $sprintf = "%s";
                $inflate = "NULL";
            } else {
                $sprintf = $c->getSprintfField( $v );
                $inflate = $c->inflateValue( $v );
            }

            # if( is_array($v) )
            array_push( $stm_columns , $k );
            array_push( $stm_parts  , "$k = $sprintf" );
            array_push( $stm_values , $inflate );
        }

        $sql = "UPDATE {$model->getTable()} SET "
                 . join(', ', $stm_parts );

        array_unshift( $stm_values , $sql );
        $sql = call_user_func_array( 'sprintf' , $stm_values );

        # $record->logger->write( $sql );
        return $sql;
    }

    protected function getJoinSQL() {
        $sql = "";
        foreach( $this->join as $key => $j ) {
            $table = $j['table'];
            $alias = $j['alias'];
            $self  = $j['self'];
            $join  = $j['join'];
            $sql .= sprintf(' LEFT JOIN %s %s ON (%s.%s = %s.%s) ',
                $table,
                $alias,
                $this->mainAlias,
                $self,
                $alias,
                $join );
        }
        return $sql;
    }

    protected function getOrderSQL() {
        if( $this->order ) {
            $sql = " ORDER BY ";

            $order_sql = array();
            foreach( $this->order as $order ) {
                $column = $order['column'];
                $ordering = $order['ordering'];
                array_push( $order_sql , "$column $ordering" );
            }
            return " ORDER BY " . join( ',' , $order_sql );
        }
        return "";
    }

    protected function getLimitSQL() {
        if( $this->limitFrom )
            return " LIMIT {$this->limitFrom},{$this->limit}";
        if( $this->limit )
            return " LIMIT {$this->limit}";
        return "";
    }

    protected function getGroupSQL() {
        if( $this->group )
            return sprintf(" GROUP BY %s" , join(',', $this->group ) );
        return "";
    }

    protected function combineConditions($params) {
        $conditions = array();
        $values = array();
        $model = $this->model;
        foreach( $params as $name => $arg ) {
            $val = null;
            $cond_sql = '=';

            /* value with operator */
            if( is_array($arg) && count($arg) == 2 ) {
                list( $cond_sql , $val ) = $arg;
            }
            else {
                $val = $arg;
            }

            // convert value into inflate value
            $sprintf = null;
            $inflate = null;
            $c = $model->getColumn( $name );

            /* if column is not defeind, it might be a join column */
            if( !$c ) {
                if( preg_match('/^(\w+).(\w+)$/',$name,$reg) ) {
                    list($orig,$alias,$cname) = $reg;
                    $j = $this->findJoinAlias( $alias );
                    if( $j ) {
                        $m = $j["model"];
                        $c = $m->getColumn( $cname );
                    }
                }
            }

            /* if column is defined ... */
            if( $c ) 
            {
                /* For case like:  
                *
                *   array('like', .... )
                *   array('!=' , ..... )
                *   array('not like' , ..... )
                *
                * raw, Do not inflate:
                *   array('raw', .... )
                *
                */
                $isRaw = false;
                if( is_array($arg) && ( $arg[0] == 'raw' || count($arg) == 1 ) )
                    $isRaw = true;

                if ($isRaw) { 
                    $sprintf = "%s";
                    $inflate = $arg[0];
                } else {
                    $sprintf = $c->getSprintfField( $val );
                    $inflate = $c->inflateValue( $val );
                }

            } else {
                // use default inflator
                list( $sprintf, $inflate ) = $this->inflateValue( $val );
            }
            array_push( $conditions , "$name $cond_sql $sprintf" );
            array_push( $values , $inflate );
        }

        // XXX: support "OR"
        $sql = join(' AND ' , $conditions );
        array_unshift( $values , $sql );
        $sql =  call_user_func_array( 'sprintf' , $values );
        return $sql;
    }


    protected function getConditionSQL() {
        if( count($this->where) == 0 )
            return "";

        $sql = " WHERE ";
        foreach( $this->where as $part ) {

            if( @$part['agg_start'] )
                $sql .= " " . $part['agg_start'] . " ( ";
            elseif( @$part['agg_end'] )
                $sql .= " ) ";
            elseif( @$part['agg'] ) {

                if( isset($part['params']) ) {
                    $part_sql = $this->combineConditions( $part['params'] );
                    if( count( $part['params'] ) > 1 ) 
                        $sql .= " " . $part["agg"] . " (" . $part_sql . ") ";
                    else
                        $sql .= " " . $part["agg"] . " " . $part_sql;
                } elseif( isset($part['statement'] ) ) {
                    $part_sql = $part['statement'];
                    $sql .= " " . $part["agg"] . " (" . $part_sql . ") ";
                }

            } else {

                if( isset($part['params']) ) {
                    $sql .= $this->combineConditions( $part['params'] );
                } else {
                    $sql .= $part['statement'];
                }

            }
        }
        return $sql;
    }

    public function buildDeleteSQL() {
        return $this->getDeleteClause()
                . $this->getConditionSQL()
                . $this->getGroupSQL()
                . $this->getOrderSQL()
                . $this->getLimitSQL();
    }

    public function buildUpdateSQL( $args ) {
        return $this->getUpdateSQL( $args )
                . $this->getConditionSQL()
                . $this->getLimitSQL();
    }

    public function buildInsertSQL( $args ) {
        return $this->getInsertClause( $args );
    }

    public function buildSelectSQL() {
        return $this->getSelectClause()
            . $this->getJoinSQL()
            . $this->getConditionSQL()
            . $this->getGroupSQL()
            . $this->getOrderSQL()
            . $this->getLimitSQL() ;
    }

    public function printSQL() {
        $sql = $this->buildSelectSQL();
        echo "SQL: " . $sql . "\n";
        return $sql;
    }

}



?>
