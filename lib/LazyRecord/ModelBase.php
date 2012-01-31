<?php
namespace LazyRecord;
use Exception;
use LazyRecord\Inflector;

/*

Validation:

When update, we should just validate those arguments which is for updating.

When create, we should validate all arguments for creating record.

    $ret = $record->validate( $args );

*/

interface ModelInterface
{
    public function load( $arg );
    public function loadOrCreate( $args );
    public function loadHash( $args );
    public function loadObject( $obj );

    public function delete( $arg = null );
    public function create( $args );
    public function update( $args );
    static function deleteWhere( $args );

    public function toArray();
    public function toJSON();
}

/*
 * ModelBase
 *
 * is a SQLExecutor
 * has a SQLBuilder
 *
 */
class ModelBase extends \LazyRecord\SQLExecutor 
    implements ModelInterface 
{

    protected  $user,
            $table,
            $label;

    /* primary key */
	public  $pk = 'id';

    /* sql builder */
    protected $_sqlBuilder;

    // preload args
    // Note that when using fetch_object() , these args is seperated.
	public $_data    = array();

    public $columns = array();

    function __construct( $args = null ) 
    {
        // default column (id)
        $this->column( $this->pk )
            ->type( "integer" )
            ->primary()->autoIncrement();
        $this->schema();

        $this->initBuilder();

        if( ! $this->getTable() ) 
            throw new Exception( "Table is not defined." );

        if( ! $this->table )
            $this->table = $this->_classnameToTable();

        if( ! $this->table )
            throw new Exception( "Table is not defined." );

        if( is_array($args) )                # data array
            $this->loadHash( $args );
        elseif( is_object($args) )           # data object
            $this->loadObject($args);
        elseif( is_integer($args) )          # record id
            $this->load($args);
    }

    /* dispatch to sql builder interface first */
    function __call( $name , $args ) 
    {
        if( method_exists($this,$name) ) {
            return call_user_func_array( array($this,$name) , $args );
        }
        elseif( method_exists( $this->_sqlBuilder , $name) ) {
            return call_user_func_array( array($this->_sqlBuilder,$name) , $args );
        }
    }

    /* support static method of 'delete', 'find' */
    public static function __callStatic($name, $arguments) 
    {
        $model = new static;
        return call_user_func_array( array($model,$name), $arguments );
    }


    /***** Data Manipulators *****/

    function __set( $name , $value ) 
    {
        $this->_data[ $name ] = $value; 
    }

    function __get( $key ) 
    {
        if( isset( $this->_data[ $key ] ) )
            return $this->_data[ $key ];
    }

    function __isset( $name )
    {
        return isset($this->_data[ $name ] );
    }

    private function _resetData()
    {
        $this->_data = array();
    }

    public function loadHash( $args ) 
    {
        $this->_data = $this->_convertArgs( $args );
    }

    // convert data here 
    //      according to the column specified type 
    //      convert them into the actual type
    private function _convertArgs( $data ) 
    {
        foreach( $data as $key => $value ) {
            $column = $this->getColumn( $key );
            if( ! $column )
                continue;

            $type =  $column->getType();
            if( ! $type )
                $type = 'string';

            if( $type == "boolean" || $type == "bool" ) {
                if( $value === true || $value == "true" || $value == "1" || $value === 1 )
                    $data[$key] = true;
                else 
                    $data[$key] = false;

            } elseif( $type == "string" 
                || $type == "integer" 
                || $type == "float"
            ) {
                settype( $data[$key] , $type );
            }
        }
        return $data;
    }



    public function refer( $modelClass ) 
    {
        $this->refer = $modelClass;
        return $this;
    }

    protected function schema() { 
        throw new Exception( _("Record schema is not defined.") );
    }


    function initBuilder() 
    {
        $this->_sqlBuilder = new SQLBuilder( $this );
        $this->_sqlBuilder->limit(1);
        return $this->_sqlBuilder;
    }

    public function getData() {
        return $this->_data;
    }

    /* it's the same thing of inflateName , but more clear */
    public function dataLabel() {
        return $this->id;
    }

    public function inflateName() {
        return $this->id;
    }

    // basic setters
    protected function table($table) { 
        $this->table = $table; 
        return $this; 
    }

    protected function label($label) { 
        $this->label = $label; 
        return $this; 
    }

    // override this method to get a custom label.
    public function getLabel() 
    {
        return $this->label 
            ? $this->label 
            : $this->_classnameToLabel();
    }

    public function getTable() 
    {
        return $this->table 
            ? $this->table 
            : $this->_classnameToTable();
    }

    protected function _classnameToLabel() {
        /* Get the latest token. */
        if( preg_match( '/\\\(\w+?)(?:Model)?$/', get_class($this), $reg) ) {
            $label = @$reg[1];
            if( ! $label )
                throw new Exception( "Table name error" );

            /* convert blah_blah to BlahBlah */
            return ucfirst(preg_replace( '/[_]/' , ' ' , $label ));
        }
    }

    protected function _classnameToTable() { 
        /* If we got Yasumi\Model\UserModel, we have to strip. */
        if( preg_match( '/(\w+?)(?:Model)?$/',get_class($this),$reg) ) {
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

    /* 
     * Method for defining column 
     * */
    protected function column( $name ) 
    {
        return $this->columns[ $name ] = new \LazyRecord\Column($name , $this );
    }

    public function getColumnNames() 
    {
		return array_keys( $this->columns );
   	}

    public function getColumnValues()
    {
        $values = array();
        foreach( $this->columns as $c ) { 
            $values[] = $this->_data[ $c->name ];
        }
        return $values;
    }


	public function getColumnLabels() 
	{
        $labels = array();
        foreach( $this->columns as $c ) { 
            $label = $c->getLabel();
            if( $label )
                $labels[] = $label;
        }
        return $labels;
    }

	public function getColumn( $name ) 
	{
        if( isset( $this->columns[ $name ] ) )
            return $this->columns[ $name ]; 
	}

    /* 
     * return column objects 
     * */
    public function getColumns()
    {
        return array_values( $this->columns );
    }


    public function value( $name ) 
    {
        if( isset($this->_data[ $name ]) )
            return $this->_data[ $name ];
   	}


    /* pass a value to a column for displaying */
    public function display( $name )
    {
        $c = $this->getColumn( $name );
        return $c->display( $this->value( $name ) );
    }


    /* get column selection values */
    public function getSelection( $name )
    {
        return $this->getColumn( $name )->getSelection();
    }



    /***** data load methods *****/
    public function loadAny()
    {
        $b = $this->initBuilder();
        $b->limit(1);
        $sql = $b->buildSelectSQL();
        $result = $this->executeSQL( $sql );
        $data = $result->fetch_assoc();
        $result->close();
        if( $data ) {
            $this->_data = $this->_convertArgs( $data );
            return true;
        }
        return false;
    }

    public function loadObject( $args ) 
    {
        $this->loadHash( (array) $args );
    }




	public function loadFromSQL( $sql ) 
	{
		$args = func_get_args();
		$sql = call_user_func_array( 'sprintf',  $args );

        /* self:: refers to DatabaseHandle class */
        $dbc = static::handle();
        $result = $dbc->query( $sql );
        $data = $result->fetch_assoc();
        $result->close();

        if( ! $data ) {
            $this->_resetData();
            return array( false , _("Can not load record") );
        }
        $this->loadHash( $data );
	}




    public function loadByCols( $params ) {
        $this->_resetData();

        $b = $this->initBuilder(); // with default options
        $b->where( $params );
        $sql = $b->buildSelectSQL();

        $result = $this->executeSQL( $sql );
        $data = $result->fetch_assoc();
        $result->close();
        if( $data ) {
            $this->_data = $this->_convertArgs( $data );
            return true;
        }
        return false;
    }

    public function loadById( $id ) 
    {
        $b = $this->initBuilder();
        $b->where( array( "id" => $id ) );
        $sql = $b->buildSelectSQL();

        $result = $this->executeSQL( $sql );
        $data = $result->fetch_assoc();
        $result->close();
        if( ! $data ) {
            $this->_resetData();
            return array( false , _("Can not load record") );
        }
        $this->loadHash( $data );
        return true;
    }

    public function load( $arg ) 
    {
        if( is_numeric($arg) || is_integer($arg) ) {
            return $this->loadById( (int) $arg );
        }
        elseif( is_array($arg) ) {
            return $this->loadByCols( $arg );
        }
        else {
            throw new Exception("Unknown Argument type for load method.");
        }
    }

    public function loadBy( $name , $value ) 
    {
        return $this->load( array( $name => $value ) );
    }



	function increase( $column ) {
        if ( ! $this->id ) 
            throw new Exception('Record is not loaded. Can not increase column value');

		$sql = sprintf("UPDATE {$this->table} SET $column = $column + 1 WHERE id = %d" , $this->id);

		// update data object
		$this->_data[ $column ]++;

		$result = $this->executeSQL( $sql );
        $result->close();
	}

    static function deleteWhere( $args ) {
        $cls = null;
        if( function_exists('get_called_class') )
            $cls = get_called_class();
        else
            $cls = get_class($this);

        $m = new $cls;
        $sqlBuilder = new SQLBuilder( $m );

        if( is_int( $args ) ) {
            $sqlBuilder->where( array( "id" => $args ) );
        }
        elseif( is_array( $args ) ) {
            $sqlBuilder->where( $args );
        }
        $sql = $sqlBuilder->buildDeleteSQL();
        return $m->executeSQL( $sql ); // return true or false
    }


    public function delete( $arg = null )
    {
        if( $arg ) {
            if( is_integer($arg) && is_numeric($arg) ) {
                $b = $this->initBuilder();
                $b->where( array( "id" => (int) $arg ) );
                $sql = $b->buildDeleteSQL();
                return $this->executeSQL( $sql );
            }
            elseif( is_array($arg) ) {
                $b->initBuilder();
                $b->where( $arg );
                $sql = $b->buildDeleteSQL();
                return $this->executeSQL( $sql );
            }
        } else {
            if( ! $this->id )
                return array( false , _("Delete: empty record.") );

#        delete related records
#        foreach( $this->columns as $name => $column ) {
# XXX: Delete related record.
#              $value = $this->get_value( $name );
#              if( isset($attr['related']) ) {
#                  list($table,$pkc) = explode('.' ,$attr['related']);
#                  $sql = sprintf("DELETE FROM {$table} where {$pkc} = %d" 
#                      , $value );
#                  $dbc->query( $sql );
#              }
#        }
            if( ! $this->id )
                throw new Exception("Delete action require record id");

            // FIXME: handle beforeDelete in Collection.
            $this->beforeDelete();
            $b = $this->initBuilder();
            $b->where( array( "id" => $this->id ) );
            $sql = $b->buildDeleteSQL();
            $rs = $this->executeSQL( $sql );
            if( $rs )
                $this->_resetData();
            $this->afterDelete();
            return $rs;
        }
    }

    public function validate( $type , $args , $all = false ) {
        if( $type == "create" ) {
            return $this->validateAll( $args , $all );
        } 
        elseif( $type == "update" ) {
            return $this->validateArgs( $args, $all , $type );
        }
        elseif( $type == "delete" ) {
            return $this->validateArgs( $args, $all , $type );
        }
        else {
            return $this->validateArgs( $args , $all );
        }
    }

    public function validateArgs( $args , $all = false , $type = null ) { 
        $results = array();
        foreach( $args as $key => $value ) {
            $column = $this->getColumn( $key );

            # skip if column is not defined.
            if( $column == null )
                continue;

            $result = $column->validate( $value , $type );
            if ( $result ) { 
                if ( $all ) 
                    $results[] = $result;
                elseif ( ! $result->ok ) 
                    return array( $result );
            }
        }
        return $results;
    }

    public function validateAll( $args, $all = false ) 
    {
        $results = array();
        foreach ( $this->columns as $name => $column ) {
            $value = @$args[ $name ];
            $result = $column->validate( $value );
            if( $result ) {
                if( $all ) 
                    $results[] = $result;
                elseif ( ! $result->ok ) 
                    return array($result);
            }
        }
        return $results;
    }

    public function validateCreate( $args ) { }
    public function validateUpdate( $args ) { }
    public function validateDelete( $args ) { }


    /*
     * Use column definition to convert string values from outside.
     * */
    public function deflateArgs( $args ) {
        foreach( $args as $k => $v ) {
            $c = $this->getColumn($k);
            if( $c )
                $args[ $k ] = $this->_data[ $k ] = $c->deflateValue( $v );
        }
        return $args;
    }

    public function beforeCreate( $args ) 
    {
        return $args;
    }

    public function afterCreate( $args ) 
    {

    }

    public function doCreate( $args ) 
    {
        $args = $this->deflateArgs( $args );

        $b = $this->initBuilder();
        $sql = $b->buildInsertSQL( $args );

        $result = $this->executeSQL( $sql );
        if( $result === false )
            throw new Exception( "Create failed" . $dbc->error );

        $id = static::handle()->insert_id;
        $this->id = $id;
        # $this->loadHash( $args );
        $this->afterCreate( $args );
        return null;
    }


    // load or create (by column)
    // return true (record has been created, and is loaded.)
    // return false (not found, created, return create return value)
    public function loadOrCreate( $args , $column = "id" ) 
    {
        if( is_string($column) ) 
            $this->load( array( $column => $args[ $column ] ) );
        else if( is_array($column) ) {
            $condition = array();
            foreach($column as $n ) {
                $condition[ $n ] = $args[ $n ];
            }
            $this->load( $condition );
        }

        if( ! $this->id ) {
            return $this->create( $args );
        }
        return true;
    }

    public function createOrUpdate( $args , $column = "id" ) 
    {
        $ret = $this->loadOrCreate( $args , $column );
        if( $ret === true ) {  // loaded
            $this->update( $args );
        }
    }


    public function getRefer( $column ) {
        $c = $this->getColumn( $column );
        if( !$c )
            throw new Exception( "Column not found: $column" );

        if( ! $c->refer )
            throw new Exception( "Refer is not defined: $column" );

        $jdata = $this->findJoinColumn( $column );

        if( $jdata ) {
            $m = $jdata['model'];
            $c_keys = $jdata->getColumnNames();
            foreach( $c_keys as $k ) {
                $self_k = $jdata["alias"] . "_" . $k;
                $m->$k = $this->self_k;
            }
            return $m;
        } else {
            $m_class = sprintf('%sModel',$c->refer);
            $m = new $m_class;
            $val = $this->_data[ $column ];
            $m->load( $val );
            return $m;
        }
    }

    public function create( $args ) 
    {
        $args = $this->beforeCreate( $args );

        
        
        /* apply default values */
        foreach( $this->columns as $column ) {
            $name = $column->getName();
            if( $name != 'id' && ! isset($args[$name] ) )
                $args[$name] = $column->getDefaultValue();
        }

        $result = $this->validate('create', $args );
        if( $result )
            return $result;

        $result = $this->validateCreate( $args );
        if( $result )
            return $result;

        if( $args == null )
            throw new Exception( 'Empty Arguments.' );
        return $this->doCreate( $args );
    }

    public function beforeUpdate( $args ) { return $args; }
    public function afterUpdate( $args ) {  }
    public function doUpdate( $args ) 
    {
        $args = $this->beforeUpdate( $args );
        $args = $this->deflateArgs( $args ); // apply args to columns

        $sql = $this->buildUpdateSQL( $args );

        if( $sql == null )
            throw new Exception( "SQL Error." );

        $result = $this->executeSQL( $sql );
        if( $result ) {
            foreach( $args as $k => $v )
                $this->_data[ $k ] = $v;
            $this->afterUpdate( $args );
        } else {
            throw new Exception( "Update failed." . $dbc->error );
            # return $dbc->error;
        } 
        return null;
    }

    public function save()
    {
        if ( ! $this->id )
            throw new Exception( 'Record is not loaded, Can not update record.' );
        return $this->update( $this->_data );
    }

    public function update( $args ) 
    {
        if ( ! $this->id )
            throw new Exception( 'Record is not loaded, Can not update record.' );

        $result = $this->validate( 'update', $args );
        if( $result )
            return $result;

        $result = $this->validateUpdate( $args );
        if( $result )
            return $result;

        return $this->doUpdate( $args );
    }





	/* FUNCTIONS FOR GENERATING SQL */


    /*
        return $sql
    */
    function getSchema() {  
        $sql = "CREATE TABLE " . $this->getTable() . "( \n";
        $column_sql = array();
        foreach( $this->columns as $name => $column ) {
            $column_sql[] = "\t" . $column->schema();
        }
        $sql .= join(",\n",$column_sql);
        $sql .= "\n);\n";
        return $sql;
    }

    /*
        return $sql
     */
    function cleanSQL() 
    {
        return sprintf( 'DROP TABLE IF EXISTS %s;' , $this->getTable() );
    }



    /*
     * Drop current table schema, rebuild schema and insert.
     * return void
     * */
    function schemaInit() {
        $this->schemaDrop();
        $db = static::handle();
        $ret = $db->query( $this->getSchema() ); 
        if( $ret === false )
            throw new Exception( $db->error );
    }

    /*
     * return void
     * */
    function schemaDrop() {
        $db = static::handle();
        $ret = $db->query( $this->cleanSQL() ) ;
        if( $ret == false ) 
            throw new Exception( $db->error );
    }



	function __toString()
	{
		return var_export( $this->_data , true );
	}

	function toArray() 
	{
        return $this->_data;
    }

	function toJSON() 
	{
        return json_encode($this->_data);
    }

    function preinit()
    {

    }

    static function getCollectionClass()
    {
        return preg_replace( '/Model$/', '' , get_called_class() ) . 'Collection';
    }


    function asCollection() 
    {
        $collectionClass = static::getCollectionClass();
        if( class_exists($collectionClass) )
            return new $collectionClass;

        /* try to load user-defined collection class */
        spl_autoload_call( $collectionClass );
        if( class_exists($collectionClass) )
            return new $collectionClass;

        $class = static::produceCollectionClass();
        return new $class;
    }

    static function produceCollectionClass()
    {
        $collectionClass = static::getCollectionClass();
        if( class_exists($collectionClass) )
            return $collectionClass;

        $modelClass = get_called_class();
        $modelName = null;
        $ns = '';
        if( ( $pos = strrpos( $modelClass , '\\' )) !== false ) {
            $ns = substr( $modelClass , 0 , $pos );
            $modelName = substr( $modelClass ,  $pos + 1 );
        } else {
            $ns = '';
            $modelName = $modelClass;
        }

        if( ($p = strrpos( $collectionClass , '\\' ) ) !== false ) {
            $collectionName = substr( $collectionClass, $p + 1 );
        } else {
            $collectionName = $collectionClass;
        }

        $code =<<<CLS
namespace $ns {
    class $collectionName extends \\LazyRecord\\Collection
    {
        public \$modelClass = '$modelClass';
    }
}
CLS;

        // XXX: save file code for cache ?
        # echo $code;
        eval($code);
        return $collectionClass;
    }

}

?>
