<?php
namespace LazyRecord\Schema\SchemaDeclare;
use Exception;

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
    public function __construct( $name )
    {
        $this->name = $name;
        $this->supportedAttributes = array(

            'primary'       => self::ATTR_FLAG,
            'autoIncrement' => self::ATTR_FLAG,
            'immutable'     => self::ATTR_FLAG,
            'unique'        => self::ATTR_FLAG, /* unique, should support by SQL syntax */
            'null'          => self::ATTR_FLAG,
            'notNull'       => self::ATTR_FLAG,
            'required'      => self::ATTR_FLAG,

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

            'defaultBuilder' => self::ATTR_CALLABLE,

            'validator'  => self::ATTR_CALLABLE,

            'validValueBuilder' => self::ATTR_CALLABLE,


            /* contains an associative array */
            'validPairs' => self::ATTR_ANY,


			// canonicalizer
			'canonicalizer' => self::ATTR_CALLABLE,

			// an alias of canonicalizer
			'filter' => self::ATTR_CALLABLE,

            'inflator' => self::ATTR_CALLABLE,

            'deflator' => self::ATTR_CALLABLE,
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


