<?php
namespace Lazy\Schema;

class Column
{
    public $name;

    public $attributes = array();

    public function __construct($name, & $attributes)
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

    public function __set($n,$v) 
    {
        return $this->attributes[$n] = $v;
    }


	public function canonicalizeValue( & $value , $record = null , $args = null )
	{
		$cb = $this->filter ?: $this->canonicalizer ?: null;
		if( $cb ) {
			return $value = call_user_func( $cb , $value , $record, $args );
		}
		return $value;
	}

	/**
	 * for an existing record, we might need the record data to return specified valid values.
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

    public function typeCasting( & $value)
    {
        if( $this->isa ) {
            if( $this->isa === 'int' ) {
                return $value = (int) $value;
            }
            elseif( $this->isa === 'str' ) {
                return $value = (string) $value;
            }
            elseif( $this->isa === 'bool' ) {
                if( is_string($value) ) {
                    if( strncasecmp($value,'false',5) == 0 ) {
                        return $value = false;
                    } elseif( strncasecmp($value,'true',4 ) == 0 ) {
                        return $value = true;
                    }
                }
                return $value = (boolean) $value;
            }
        }
        return $value;
    }

	public function checkTypeConstraint($value)
	{
		if( $this->isa )
		{
			if( $this->isa === 'str' ) {
				if( false === is_string( $value ) ) 
					return 'Value is not a string value.';
			}
			elseif( $this->isa === 'int' ) {
				if( false === is_integer( $value ) )
					return 'Value is not a integer value.';
			}
			elseif( $this->isa === 'bool' || $this->isa === 'boolean' ) {
				if( false === is_bool( $value ) )
					return 'Value is not a boolean value.';
			}
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

    public function display( $value )
    {
        if( $this->validPairs && isset( $this->validPairs[ $value ] ) )
            return $this->validPairs[ $value ];

        if( $this->isa == 'bool' )
            return $value ? _('Yes') : _('No');

        if( $value )
            return _( $value );

        return $value;
    }

    public function getLabel()
    {
        if( $this->label )
            return _( $this->label );
    }

}



