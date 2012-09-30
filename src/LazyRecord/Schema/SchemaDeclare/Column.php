<?php
namespace LazyRecord\Schema\SchemaDeclare;
use Exception;


/**
 * Postgresql Data Types:
 * @link http://www.postgresql.org/docs/9.1/interactive/datatype.html
 *
 * MySQL Data Types:
 * @link http://dev.mysql.com/doc/refman/5.0/en/data-types.html
 *
 *
 * Blob type:
 *
 * @link http://dev.mysql.com/doc/refman/5.0/en/blob.html (MySQL)
 * @link http://www.postgresql.org/docs/9.1/interactive/datatype-binary.html (Postgresql)
 */
class Column 
{
    const  ATTR_ANY = 0;
    const  ATTR_ARRAY = 1;
    const  ATTR_STRING = 2;
    const  ATTR_INTEGER = 3;
    const  ATTR_FLOAT = 4;
    const  ATTR_CALLABLE = 5;
    const  ATTR_FLAG = 6;

    /**
     * @var string column name
     */
    public $name;

    /**
     * @var array $supportedAttributes
     */
    public $supportedAttributes = array();

    /**
     * @var array $attributes
     */
    public $attributes = array(
        'type' => 'text',
        'isa' => 'str',
    );

    /**
     * @var string $name column name (id)
     */
    public function __construct( $name = null )
    {
        $this->supportedAttributes = array(

            'primary'       => self::ATTR_FLAG,
            'size'          => self::ATTR_INTEGER,
            'autoIncrement' => self::ATTR_FLAG,
            'immutable'     => self::ATTR_FLAG,
            'unique'        => self::ATTR_FLAG, /* unique, should support by SQL syntax */
            'null'          => self::ATTR_FLAG,
            'notNull'       => self::ATTR_FLAG,
            'required'      => self::ATTR_FLAG,
            'typeConstraint' => self::ATTR_FLAG,
            'enum'          => self::ATTR_ARRAY,

            /* column label */
            'label' => self::ATTR_ANY,

            'desc'  => self::ATTR_STRING,

            'comment'  => self::ATTR_STRING,


            /* reference to model schema */
            'refer' => self::ATTR_STRING,


            /* data type: string, integer, DateTime, classname */
            'isa' => self::ATTR_STRING,

            'type' => self::ATTR_STRING,

            'default' => self::ATTR_ANY,

            'validator'  => self::ATTR_ANY,

            'validatorArgs'  => self::ATTR_ANY,

            'validValues' => self::ATTR_ANY,

            'validValueBuilder' => self::ATTR_CALLABLE,


            /* contains an associative array */
            'validPairs' => self::ATTR_ANY,


            // canonicalizer
            'canonicalizer' => self::ATTR_CALLABLE,

            'virtual' => self::ATTR_FLAG,

            // an alias of canonicalizer
            'filter' => self::ATTR_CALLABLE,

            'inflator' => self::ATTR_CALLABLE,

            'deflator' => self::ATTR_CALLABLE,

            // renderAs widget
            'renderAs' => self::ATTR_STRING,

            'widgetAttributes' => self::ATTR_ARRAY,
        );

        if( $name ) {
            $this->name = $name;
        }
    }

    public function name($name) 
    {
        $this->name = $name;
        return $this;
    }

    public function varchar($size)
    {
        $this->attributes[ 'type' ] = 'varchar(' . $size . ')';
        $this->attributes[ 'isa' ]  = 'str';
        $this->attributes[ 'size' ]  = $size;
        return $this;
    }

    public function char($limit)
    {
        $this->attributes[ 'type' ] = 'char(' . $limit . ')';
        $this->attributes[ 'isa'  ] = 'str';
        $this->attributes[ 'size' ]  = $limit;
        return $this;
    }



    /**
     * PgSQL supports double, real.
     *
     * XXX: support for 'Infinity' '-Infinity' 'NaN'.
     *
     *
     * MySQL supports float, real, double:
     *      float(3), float, real, real(10)
     *
     * MySQL permits a nonstandard syntax: FLOAT(M,D) or REAL(M,D) or DOUBLE 
     * PRECISION(M,D). Here, “(M,D)” means than values can be stored with up 
     * to M digits in total, of which D digits may be after the decimal point. 
     * For example, a column defined as 
     *      FLOAT(7,4) will look like -999.9999 when displayed. 
     *
     * MySQL performs rounding when storing values, so if you 
     * insert 999.00009 into a FLOAT(7,4) column, the approximate result is 
     * 999.0001.
     *
     * @link http://dev.mysql.com/doc/refman/5.0/en/floating-point-types.html
     *
     * XXX: 
     * we should handle exceptions when number is out-of-range:
     * @link http://dev.mysql.com/doc/refman/5.0/en/out-of-range-and-overflow.html
     */
    public function double($m = null, $d = null)
    {
        if( $m && $d ) {
            $this->attributes['type'] = "double($m,$d)";
        }
        elseif( $m ) {
            $this->attributes['type'] = "double($m)";
        }
        else {
            $this->attributes['type'] = 'double';
        }
        $this->attributes['isa'] = 'double';
        return $this;
    }

    public function float($m = null ,$d = null)
    {
        if( $m && $d ) {
            $this->attributes['type'] = "float($m,$d)";
        }
        elseif( $m ) {
            $this->attributes['type'] = "float($m)";
        }
        else {
            $this->attributes['type'] = 'float';
        }
        $this->attributes['isa']  = 'float';
        return $this;
    }

    public function tinyint() 
    {
        $this->attributes['type'] = 'tinyint';
        $this->attributes['isa'] = 'int';
        return $this;
    }

    public function timestamp()
    {
        $this->attributes['type'] = 'timestamp';
        $this->attributes['isa'] = 'DateTime';
        return $this;
    }

    public function text()
    {
        $this->attributes['type'] = 'text';
        $this->attributes['isa'] = 'str';
        return $this;
    }

    public function smallint()
    {
        $this->attributes['type'] = 'smallint';
        $this->attributes['isa'] = 'int';
        return $this;
    }

    public function bigint()
    {
        $this->attributes['type'] = 'bigint';
        $this->attributes['isa'] = 'int';
        return $this;
    }

    public function integer()
    {
        $this->attributes['type'] = 'integer';
        $this->attributes['isa'] = 'int';
        return $this;
    }

    public function bool()
    {
        return $this->boolean();
    }

    public function boolean()
    {
        $this->attributes['type'] = 'boolean';
        $this->attributes['isa'] = 'bool';
        return $this;
    }

    public function blob()
    {
        $this->attributes['type'] = 'blob';
        $this->attributes['isa'] = 'str';
        return $this;
    }

    public function binary()
    {
        $this->attributes['type'] = 'binary';
        $this->attributes['isa'] = 'str';
        return $this;
    }

    public function enum()
    {
        $this->attributes['type'] = 'enum';
        $this->attributes['isa'] = 'enum';
        $this->attributes['enum'] = func_get_args();
        return $this;
    }

    /**
     * serial type
     *
     * for postgresql-only
     */
    public function serial()
    {
        $this->attributes['type'] = 'serial';
        $this->attributes['isa'] = 'int';
        return $this;
    }



    /************************************************
     * DateTime related types
     *************************************************/

    public function date()
    {
        $this->attributes['type'] = 'date';
        $this->attributes['isa'] = 'DateTime';
        return $this;
    }

    public function datetime()
    {
        $this->attributes['type'] = 'datetime';
        $this->attributes['isa'] = 'DateTime';
        return $this;
    }


    public function renderAs($renderAs,$widgetAttributes = array() ) {
        $this->renderAs = $renderAs;
        $this->widgetAttributes = $widgetAttributes;
        return $this;
    }


    /**
     * Use referenece from existing relationship 
     *
     * @param string $relationship relationship id
     */
    public function refer($relationship)
    {
        $this->attributes['refer'] = $relationship;
        return $this;
    }

    public function autoIncrement()
    {
        $this->autoIncrement = true;
        $this->type = 'integer';
        $this->isa = 'int';
        return $this;
    }

    public function validator() {
        $args = func_get_args();
        if( count($args) == 1 && is_callable($args[0]) ) {
            $this->attributes['validator'] = $args[0];
            return $this;
        }
        elseif( is_string($args[0]) ) {
            $arg = $args[0];
            if( is_a($arg,'ValidationKit\Validator',true) ) {
                $this->attributes['validator'] = $args[0];
                if(isset($args[1]))
                    $this->attributes['validatorArgs'] = $args[1];
                return $this;
            }

            // guess class name
            $c = 'ValidationKit\\' . $arg;
            if( is_a($c, 'ValidationKit\\Validator',true) ) {
                $this->attributes['validator'] = $c;
                if(isset($args[1]))
                    $this->attributes['validatorArgs'] = $args[1];
                return $this;
            }

            $c = 'ValidationKit\\' . $arg . 'Validator';
            if( is_a($c, 'ValidationKit\\Validator',true) ) {
                $this->attributes['validator'] = $c;
                if(isset($args[1]))
                    $this->attributes['validatorArgs'] = $args[1];
                return $this;
            }
        }
        $this->attributes['validator'] = $args[0];
    }

    public function export()
    {
        return array(
            'name' => $this->name,
            'attributes' => $this->attributes,
        );
    }

    public function toArray()
    {
        $attrs = $this->attributes;
        $attrs['name'] = $this->name;
        return $attrs;
    }

    public function dump()
    {
        return var_export( $this->export() , true );
    }

    public function __isset($name)
    {
        return isset( $this->attributes[ $name ] );
    }

    public function __get($name)
    {
        if( isset( $this->attributes[ $name ] ) )
            return $this->attributes[ $name ];
    }

    public function __set($name,$value)
    {
        $this->attributes[ $name ] = $value;
    }

    public function __call($method,$args)
    {
        if( isset($this->supportedAttributes[ $method ] ) ) {
            $c = count($args);
            $t = $this->supportedAttributes[ $method ];

            if( $t != self::ATTR_FLAG && $c == 0 ) {
                throw new Exception( 'Attribute value is required.' );
            }

            switch( $t ) {

                case self::ATTR_ANY:
                    $this->attributes[ $method ] = $args[0];
                    break;

                case self::ATTR_ARRAY:
                    if( $c > 1 ) {
                        $this->attributes[ $method ] = $args;
                    }
                    elseif( is_array($args[0]) ) 
                    {
                        $this->attributes[ $method ] = $args[0];
                    } 
                    else
                    {
                        $this->attributes[ $method ] = (array) $args[0];
                    }
                    break;

                case self::ATTR_STRING:
                    if( is_string($args[0]) ) {
                        $this->attributes[ $method ] = $args[0];
                    }
                    else {
                        throw new Exception("attribute value of $method is not a string.");
                    }
                    break;

                case self::ATTR_INTEGER:
                    if( is_integer($args[0])) {
                        $this->attributes[ $method ] = $args[0];
                    }
                    else {
                        throw new Exception("attribute value of $method is not a integer.");
                    }
                    break;

                case self::ATTR_CALLABLE:

                    /**
                     * handle for __invoke, array($obj,$method), 'function_name 
                     */
                    if( is_callable($args[0]) ) {
                        $this->attributes[ $method ] = $args[0];
                    } else {
                        throw new Exception("attribute value of $method is not callable type.");
                    }
                    break;

                case self::ATTR_FLAG:
                    if( isset($args[0]) ) {
                        $this->attributes[ $method ] = $args[0];
                    } else {
                        $this->attributes[ $method ] = true;
                    }
                    break;

                default:
                    throw new Exception("Unsupported attribute type: $method");
            }
            return $this;
        }

        // save unknown attribute by default
        $this->attributes[ $method ] = $args[0];
        return $this;
    }

}


