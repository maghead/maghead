<?php
namespace LazyRecord\SchemaDeclare;
use Exception;

class Column 
{
    const  attr_any = 0;
    const  attr_array = 1;
    const  attr_string = 2;
    const  attr_integer = 3;
    const  attr_float = 4;
    const  attr_callable = 5;
    const  attr_flag = 6;

    /**
     * @var string column name
     */
    public $name;
    public $supportedAttributes = array();
    public $attributes = array();

    public function __construct( $name )
    {
        $this->name = $name;
        $this->supportedAttributes = array(

            'primary' => self::attr_flag,

            'autoIncrement' => self::attr_flag,

            'immutable' => self::attr_flag,

            /* unique, should support by SQL syntax */
            'unique' => self::attr_flag,

            'null' => self::attr_flag,

            'notNull' => self::attr_flag,

            'required' => self::attr_flag,


            /* column label */
            'label' => self::attr_string,
            'desc'  => self::attr_string,
            'comment'  => self::attr_string,


            /* data type: string, integer, DateTime, classname */
            'isa' => self::attr_string,

            'type' => self::attr_string,

            'default' => self::attr_any,

            'defaultBuilder' => self::attr_callable,

            'validator'  => self::attr_callable,

            'validValueBuiler' => self::attr_callable,
        );
    }

    public function varchar($size)
    {
        $this->attributes[ 'type' ] = 'varchar(' . $size . ')';
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

    public function export()
    {
        return var_export( $this->attributes , true );
    }

    public function __call($method,$args)
    {
        if( isset($this->supportedAttributes[ $method ] ) ) {
            $c = count($args);
            $t = $this->supportedAttributes[ $method ];

            if( $t != self::attr_flag && $c == 0 )
                throw new Exception( 'Attribute value is required.' );

            switch( $t ) {
                case self::attr_any:
                    $this->attributes[ $method ] = $args[0];
                    break;
                case self::attr_array:
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
                case self::attr_string:
                    if( is_string($args[0]) ) {
                        $this->attributes[ $method ] = $args[0];
                    }
                    else {
                        throw new Exception("attribute value of $method is not a string.");
                    }
                    break;

                case self::attr_integer:
                    if( is_integer($args[0])) {
                        $this->attributes[ $method ] = $args[0];
                    }
                    else {
                        throw new Exception("attribute value of $method is not a integer.");
                    }
                    break;

                case self::attr_callable:

                    /**
                     * handle for __invoke, array($obj,$method), 'function_name 
                     */
                    if( is_callable($args[0]) ) {
                        $this->attributes[ $method ] = $args[0];
                    } 
                    else {
                        throw new Exception("attribute value of $method is not callable type.");
                    }
                    break;

                case self::attr_flag:
                    $this->attributes[ $method ] = true;
                    break;

                default:
                    throw new Exception("Unsupported attribute type: $method");
            }
            return $this;
        }

    }

}


