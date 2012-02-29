<?php
namespace Lazy\Schema;

class Column
{
    public $name;

    private $attributes = array();

    function __construct($name, & $attributes)
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }

    public function __isset($name)
    {
        return isset( $this->attributes[ $name ] );
    }

    public function __get($name)
    {
        if( isset($this->attributes[$name]) )
            return $this->attributes[$name];
    }


	/**
	 * xxx:
	 *   for an existing record, we might need the record data to return specified valid values.
	 */
	public function getValidValues( $record = null , $args = null )
	{
		if( $this->validValues ) {
			return $this->validValues;
		} elseif( $this->validValueBuilder ) {
			return call_user_func( $this->validValueBuilder , $record , $args );
		}
	}

	public function getDefaultValue( $record = null, $args = null )
	{
		if( $this->defaultBuilder ) {
			return call_user_func( $this->defaultBuilder , $record, $args );
		}
		elseif( $this->default ) {
			return $this->default; // might contains array() which is a raw sql statement.
		}
	}

    /** 
     * deflate value 
     **/
    public function deflate( $value )
    {
        // check self type, do type casting
        return \Lazy\Deflator::deflate( $value , $this->isa );
    }

}



