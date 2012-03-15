<?php
namespace Lazy\Schema\SchemaDeclare;
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
    public function __construct( $name )
    {
        $this->name = $name;
        $this->supportedAttributes = array(

            'primary'       => self::attr_flag,
            'autoIncrement' => self::attr_flag,
            'immutable'     => self::attr_flag,
            'unique'        => self::attr_flag, /* unique, should support by SQL syntax */
            'null'          => self::attr_flag,
            'notNull'       => self::attr_flag,
            'required'      => self::attr_flag,

            /* column label */
            'label' => self::attr_any,

            'desc'  => self::attr_string,

            'comment'  => self::attr_string,


            /* reference to model schema */
            'refer' => self::attr_string,


            /* data type: string, integer, DateTime, classname */
            'isa' => self::attr_string,

            'type' => self::attr_string,

            'default' => self::attr_any,

            'defaultBuilder' => self::attr_callable,

            'validator'  => self::attr_callable,

            'validValuesBuilder' => self::attr_callable,


            /* contains an associative array */
            'validPairs' => self::attr_any,


			// canonicalizer
			'canonicalizer' => self::attr_callable,

			// an alias of canonicalizer
			'filter' => self::attr_callable,
        );
    }

    public function varchar($size)
    {
        $this->attributes[ 'type' ] = 'varchar(' . $size . ')';
		$this->attributes[ 'isa' ]  = 'str';
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

    public function datetime()
    {
        $this->attributes['type'] = 'datetime';
        $this->attributes['isa'] = 'DateTime';
        return $this;
    }

    public function refer($class)
    {
        $this->attributes['isa'] = 'int';
        $this->attributes['type'] = 'integer';
        return $this;
    }

    public function autoIncrement()
    {
        $this->autoIncrement = true;
        $this->type = 'integer';
		$this->isa = 'int';
        return $this;
    }

    public function export()
    {
        return array(
            'name' => $this->name,
            'attributes' => $this->attributes,
        );
    }

    public function dump()
    {
        return var_export( $this->export() , true );
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

            if( $t != self::attr_flag && $c == 0 ) {
                throw new Exception( 'Attribute value is required.' );
            }

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
                    } else {
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

        // save unknown attribute by default
        $this->attributes[ $method ] = $args[0];
        return $this;
    }

}


