<?php


/*

list column type field value table

                field       value     internal value
    text         %s           '%s'
    timestamp    %s           '%s'      integer
    date         %s           '%s'      
    time         %s           '%s'
    boolean      %s           %s
    integer      %d           %d
    float        %f           %f

*/


/*
    $this->index( "index_name" , array("food","test") );
 */
namespace LazyRecord;
use LazyRecord\ValidateResult;

class Column 
//    extends \CascadingAttribute 
{ 

    // SQL Attributes:
    public $primary       = false;
    public $autoIncrement = false;
    public $unique        = false;
    public $increment     = false;
    public $isNull        = true;
    public $immutable     = false;
    public $type          = "text";
    public $refer;         // refer attribute (model name)

    // save Model object
    public $record; 

    // default sql for value, this is for schema generation
    public $defaultSqlValue;

    public $hasOne;
    public $hasMany;

    public $hasA;

    public $name;
    public $label; /* column label */
    public $desc; /* description */

    public $validPairs   = array();
    public $validValues  = array();
    public $validator    = null;
    public $value;

    # default value
    public $defaultValue;
    public $defaultFunction;

    public $required = false; 
    public $completer = null;

    function __construct( $name , $record = null ) 
    {
        $this->name = $name; 
        if( $record )
            $this->record = & $record;
    }


    /****** attribute setter methods *******/
    public function type( $type ) 
    {
        $this->type = $type; 
        return $this; 
    }

    public function varchar( $length = null ) 
    {
        $this->type = $length ? 'varchar(' . $length . ')' : 'varchar';
        return $this;
    }

    public function timestamp()
    {
        $this->type = 'timestamp';
        return $this;
    }

    public function text()
    {
        $this->type = 'text';
        return $this;
    }

    public function integer()
    {
        $this->type = 'integer';
        return $this;
    }

    public function boolean()
    {
        $this->type = 'boolean';
        return $this;
    }

    public function primary($bool = true) 
    {
        $this->primary = $bool; 
        return $this; 
    }

    public function autoIncrement($bool = true) 
    {
        $this->autoIncrement = $bool; 
        return $this; 
    }

    public function charset( $charset ) 
    {
        $this->charset = $charset; 
        return $this; 
    }

    public function refer( $modelName ) 
    { 
        $this->refer = $modelName; 
        $this->type  = "integer"; 
        return $this; 
    }

    public function unique() 
    { 
        $this->unique = true; 
        return $this; 
    }

    public function null() 
    { 
        $this->isNull = true; 
        return $this; 
    }

    public function notNull() 
    {
        $this->isNull = false; 
        return $this;
    }

    # When inserting, sql function name will be in sql statement.
    public function defaultSqlValue( $value ) 
    {
        $this->defaultSqlValue = $value; 
        return $this;
    }

	/*
		$this->column('foo')->valid_pairs( range( 1, 100 ) );
		$this->column('foo')->valid_pairs( array( "male" => ... , "female" => ... ) );
	*/
    public function validPairs( $pairs ) 
    { 
        $this->validPairs = $pairs; 
        return $this;
    }


	/*
		$this->column('foo')->validValues( range( 1, 100 ) );
		$this->column('foo')->validValues( array( "male" , "female" ) );
	*/
    public function validValues( $values ) 
    {
        if( is_array( $values ) ) {
            $this->validValues = $values; 
        } elseif ( is_callable( $values ) ) {
            $this->validValues = call_user_func($values);
        } elseif ( is_string($values) ) {
            $this->validValues = explode( ' ', $values );
        } else {
            throw new Exception('Unsupported valid values');
        }
        return $this;
    }

    public function desc( $desc )
    {
        $this->desc = $desc;
        return $this;
    }

    public function label( $label ) 
    {
        $this->label = $label; 
        return $this; 
    }

    public function value( $value ) 
    {
        $this->value = $value; 
        return $this; 
    }

    public function defaultValue( $value ) 
    {
        $this->defaultValue = $value; 
        return $this; 
    }

    public function required() 
    {
        $this->required = true; 
        return $this; 
    }

    public function validator( $func_name ) 
    {
        $this->validator = $func_name; 
        return $this; 
    }






    /****** getter methods ******/
    public function getCurrentValue()
    {
        if( $this->record && isset( $this->record->data[ $this->name ] ) )
            return $this->record->data[ $this->name ];

        $val = $this->getDefaultValue();
        if( $val !== null )
            return $val;
    }

    public function getType() 
    { 
        return $this->type; 
    }

    public function getLabel() 
    {
        if( $this->label )
            return $this->label; 
        else
            return _( ucwords( $this->name ) );
    }

    public function getName() { 
        return $this->name; 
    }

    public function getDefaultValue() {

        if( $this->defaultFunction ) {
            $val =  call_user_func( $this->defaultFunction );
            if( $val ) {
                $this->value( $val );
                return $val;
            }
        }

        if( is_callable( $this->defaultValue ) ) {
            $def = $this->defaultValue;
            $val = call_user_func_array($def,array());
            if( $val ) {
                $this->value( $val );
                return $val;
            }
        }
        return $this->defaultValue;
    } 

    function getValidValues() { 
        return $this->validValues; 
    }

    function getValidPairs()  { 
        return $this->validPairs;  
    }


#      function __toString() 
#      {
#          return $this->value;
#      }


    # METHODS
    public function validateSelf() 
    {
        $value = $this->getCurrentValue();
        return $this->validate( $value );
    }

    /* validators , XXX: think of this later */
    public function validate( $value ) { 
        // if( $value )
        //    $this->value( $value );
        // $value = $this->get_value();
        if( $value == null ) {
            if( $this->getDefaultValue() )
                $value = $this->getDefaultValue();
        }
 

        if( $this->required ) { 
            if ( $value == null )
                return new ValidateResult( $this->name , 
                    false , sprintf( _("Please enter %s field."), $this->label ? $this->label : $this->name ) );
        }


        if( $value && $this->validValues ) {
            if( ! in_array( $value , $this->validValues ) )
                return new ValidateResult( $this->name , 
                    false , sprintf(_("%s is not correct."), $this->label ? $this->label : $this->name) );
        }

        if( $value && $this->validPairs ) {
            if( ! in_array( $value , array_keys($this->validPairs) ) )
                return new ValidateResult( $this->name , 
                    false , sprintf(_("%s is not correct."), $this->label ? $this->label : $this->name) );
        }

        if ( $this->validator ) {
            $ret = call_user_func( $this->validator, $this , $value );

            if( is_array($ret) )
                return new ValidateResult( $this->name, $ret[0] , @$ret[1] , @$ret[2] );
            elseif ( is_bool($ret) )
                return new ValidateResult( $this->name , $ret , $ret ? "Valid" : "Invalid" );
            return $ret;
        }
    }


    public function display( $value )
    {
        if( $this->validPairs && isset( $this->validPairs[ $value ] ) )
            return $this->validPairs[ $value ];

        if( $this->isBoolean() )
            return $value ? _('Yes') : _('No');
        if( $value )
            return _( $value );
        return $value;
    }


    public function getSelection( )
    {
        if( $this->validPairs )
            return $this->validPairs;
        if( $this->validValues )
            return $this->validValues;
        return array();
    }

    /* private methods */
    public function getSprintfField( $value = null )
    {
        $type = $this->getType();

        // for default sql we just return an unquote %s
        if( $value === null ) {
            if( $this->isNull )
                return "%s";
        }

        // respect column type
        $field = null;

        if( $type ) {
            if( $type == "text" ) 
                return "'%s'";
            elseif( $type == "timestamp" || $type == "datetime" || $type == "time" || $type == "date" )
                return "'%s'";
            elseif( $type == "integer" )
                return "%d";
            elseif( $type == "float" )
                return "%f";
            elseif( $type == "bool" || $type == "boolean" )
                return "%s";
            else 
                return "'%s'";
        }
        else {
            throw new Exception( "Column {$this->name} type undefined." );

            if( is_string($value) )
                $field = "'%s'";
            elseif( is_numeric( $value ) ) 
                $field = "%d";
            elseif( is_bool( $value ) )
                $field = "%s";
            else
                $field = "'%s'";
        }
        return $field;
    }


    /* update increment , increment a column value when updating row. */
    function increment() { 
        $this->increment = true;
        return $this;
    }

    function schema() {      
        $type = $this->getType();
        if( $type == "string" )
            $type = "text";

        $sql = sprintf('%s %s',$this->name,$type);

        if( $this->required )
            $sql .= " not null";


        if( $this->defaultSqlValue ) {
            $sql .= " default " . $this->defaultSqlValue;
        } else if( $this->defaultValue !== null ) {
            $v = $this->defaultValue;

            if( is_bool($v) ) {
                if( $v === true )
                    $v = 'true';
                elseif( $v === false )
                    $v = 'false';
            } elseif( is_string($v) ) {
                $v = "'$v'";
            }

            if( ! is_callable( $v ) )
                $sql .= " default " . $v;
        }

        if( $this->primary )
            $sql .= " primary key";
        
        if( $this->autoIncrement )
            $sql .= " auto_increment";

        if( $this->unique ) {
            /* XXX: try to support:
                CONSTRAINT uc_PersonID UNIQUE (P_Id,LastName)
        
                ADD UNIQUE (P_Id)

                DROP INDEX uc_PersonID
            */
            $sql .= " unique";

        }

        return $sql;
    }


    public function useValidator() { 
        $this->validator = array( $this->record , "validate_" . $this->name );
        return $this;
    }

    public function defaultFunction($func) {
        $this->defaultFunction = $func;
        return $this;
    }

    /* 
        
        (inflate: OUT TO Database)
        convert value into database (to String)
            datetime:  "2010-01-01" or ts => '2010-01-01'
            time:      "01:01"
            integer:   2010
            boolean:   true, false => true, false
            string:    "string" => "string" 
            float:     1.234  => 1.234
            blob:      ...
            binary:    ...


        (deflate: INTO Data Model)
        convert value from database
            datetime:  "2010-01-01" or ts => '2010-01-01'
            time: 
            integer:   "2010" => 2010
            boolean:   true, false => true, false
            string:    "string" => "string" 
            float:     1.234  => 1.234
            blob:      ...
            binary:    ...

            NULL => NULL

        (deflate: INTO Data Model)
        convert value from Form Post

            datetime:  "2010-01-01" => strtotime() => date('c')
            time: 
            integer:   "2010"  => 2010
            boolean:   "1","0","false","true"  => true, false, false , ture
            string     "...." => "...."
            float:     "1.234" => 1.234
            blob:  ...
            binary:  ...

     */

    /* Basic MySQL Type Category */
    function isString() 
    {
        return preg_match('/char|varchar|text|binary|varbinary|blob|enum|set/i' , $this->type );
    }

    function isBoolean() 
    {
        return $this->type == "boolean" || $this->type == "bool";
    }

    function isNumber() 
    {
        return $this->isInteger() 
                || $this->is_float() 
                || preg_match('/bit|numeric|decimal/i', $this->type );
    }

    function isFloat() { 
        return preg_match( '/double|float/i' , $this->type );
    }

    function isInteger() { 
        return preg_match( '/tinyint|smallint|mediumint|bigint|int|integer/i', $this->type );
    }

    function isDatetime() { 
        return preg_match('/date|time|datetime|year|timestamp/i' , $this->type );
    }

    function escapeString( $str ) {
        $db = \LazyRecord\Engine::getInstance();
        return $db->connection()->handle()->real_escape_string( $str );

#          elseif( function_exists('mysqli_real_escape_string') )
#              return mysqli_real_escape_string( $string );
        return mysql_escape_string( $str );
    }

    // deflate values to Data Model.
    function deflateValue($value) 
    {
        $type = $this->getType();

        if( $this->isDatetime() ) {

            // XXX: time,year data can't be parsed by strtotime() function.
            if( $type == "time" || $type == "year" )
                return $value;

            // it's a timestamp
            if( is_numeric( $value ) )
                return date('c',$value);

            // try to parse datetime string
            elseif( is_string( $value ) )
                return date('c',strtotime( $value ));

            // datetime object
            elseif( is_object( $value ) && get_class( $value ) )
                return $value->format('c');

            // XXX: unknown
            else
                return $value;
        }
        if( $this->isBoolean() ) {
            if( $value === true || $value === false )
                return $value;
            elseif( strtolower($value) == "false" )
                return false;
            elseif( strtolower($value) == "true" )
                return true;
            else
                return $value ? true : false;
        }
        if( $this->isString() ) {
            return $value;
        }
        if( $this->isFloat() ) {
            return (float) $value;
        }
        if( $this->isInteger() ) {
            return (int) $value;
        }
        if( $this->isNumber() ) {
            return (int) $value;
        }
    }


    // XXX: Move default value and default sql value out.
    function inflateValue( $value ) 
    {
        $type = $this->getType();

        if( $value === null )
            return "NULL";

        if( $this->isString() ) {
            return $this->escapeString( $value );
        }
        if( $this->isDatetime() ) {

            if( $type == "time" ) {
                $dur = \LazyRecord\Duration::parse( $value );
                return $dur->__toString();
            }

            // XXX: time,year data can't be parsed by strtotime() function.
            if( $type == "year" ) {
                if( preg_match('/^\d{4}$/' , $value ) )
                    return (int) $value;
                else
                    return 0; // XXX: is that a correct value ?
            }

            if( is_numeric( $value ) )
                return date('c',$value);  # translate timestamp to date string
            elseif( is_string( $value ) ) 
                return date('c',strtotime( $value ));
            elseif( is_object( $value ) )
                return $value->format('c');
            else
                return $value;
        }
        if( $this->isInteger() ) {
            settype( $value , "integer" );
            return $value;
        }
        if( $this->isBoolean() ) {
            return $value ? "TRUE" : "FALSE";
        }
        if( $this->isFloat() ) {
            settype( $value , "float" );
            return $value;
        }
        return $value;
    }

}


