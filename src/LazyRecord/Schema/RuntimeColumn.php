<?php
namespace LazyRecord\Schema;
use DateTime;
use LazyRecord\Deflator;
use LazyRecord\Inflator;
use Exception;

class RuntimeColumn
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

            if( is_callable($this->validValues) ) {
                return call_user_func( $this->validValues, $record, $args );
            }
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
            if( is_callable( $this->default ) ) {
                return call_user_func( $this->default, $record, $args );
            }
            return $this->default; // might contains array() which is a raw sql statement.
        }
    }



    /**
     * Column value type casting
     *
     *
     * @param mixed $value referenced value
     */
    public function typeCasting( & $value)
    {
        if( $this->isa ) {
            if( $this->isa === 'int' ) {
                return $value = (int) $value;
            }
            elseif( $this->isa === 'str' ) {
                return $value = (string) $value;
            }
            elseif( $this->isa === 'bool' || $this->isa === 'boolean' ) {

                if( is_string($value) ) 
                {
                    if( $value == null || $value === '' ) {
                        return $value = false;
                    }
                    elseif( $value === '1' ) {
                        return $value = true;
                    }
                    elseif( $value === '0' ) {
                        return $value = false;
                    }
                    elseif( strncasecmp($value,'false',5) == 0 ) {
                        return $value = false;
                    } 
                    elseif( strncasecmp($value,'true',4 ) == 0 ) {
                        return $value = true;
                    }
                }

                $value = (boolean) $value;
                return $value;
            }
        }
        return $value;
    }

    public function checkTypeConstraint($value)
    {
        if( $this->isa )
        {
            if( $this->isa === 'str' ) {
                if( ! is_string( $value ) ) {
                    throw new Exception('Value is not a string value. ' . "($value)");
                }
            }
            elseif( $this->isa === 'int' ) {
                if( ! is_integer( $value ) )
                    throw new Exception( 'Value is not a integer value.' );
            }
            elseif( $this->isa === 'bool' || $this->isa === 'boolean' ) {
                if( ! is_bool( $value ) )
                    throw new Exception( 'Value is not a boolean value.' );
            }
        }
    }

    /** 
     * deflate value 
     **/
    public function deflate( $value )
    {
        // run column specified deflator
        if( $this->deflator ) {
            return call_user_func( $this->deflator, $value );
        }

        // use global deflator, check self type, and do type casting
        return Deflator::deflate( $value , $this->isa );
    }

    public function inflate( $value )
    {
        if( $this->inflator ) {
            return call_user_func( $this->inflator , $value );
        }
        // use global inflator
        return Inflator::inflate( $value , $this->isa );
    }


    public function display( $value )
    {
        if( $this->validPairs && isset( $this->validPairs[ $value ] ) ) {
            return $this->validPairs[ $value ];
        }

        if( $this->validValues ) {
            if( is_callable($this->validValues) ) {
                $validValues = call_user_func( $this->validValues );
            } else {
                $validValues = $this->validValues;
            }

            if( $validValues && isset( $validValues[ $value ] ) ) {
                return $this->validValues[ $value ]; // value => label
            }
        }

        if( $this->validValueBuilder && $values = call_user_func($this->validValueBuilder) ) {
            if( isset($values[ $value ]) ) {
                return $values[ $value ];
            }
        }

        if( $this->isa == 'bool' )
            return $value ? _('Yes') : _('No');

        if( $value ) {
            if( is_string($value) ) {
                return _( $value );
            } 
            // quick inflator for DateTime object.
            elseif( is_a($value,'DateTime') ) {
                return $value->format( DateTime::ATOM );
            }
        }
        return $value;
    }

    public function getLabel()
    {
        if( $this->label ) {
            return _( $this->label );
        }
    }

}



