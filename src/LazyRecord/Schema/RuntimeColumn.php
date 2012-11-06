<?php
namespace LazyRecord\Schema;
use DateTime;
use LazyRecord\Deflator;
use LazyRecord\Inflator;
use LazyRecord\ArrayUtils;
use LazyRecord\Utils;
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



    /**
     * Canonicalize a value before updating or creating
     *
     * The canonicalize handler takes the original value ($value), current 
     * record ($record) and the arguments ($args)
     *
     * @param mixed $value
     * @param BaseModel $record
     * @param array $args
     *
     * @return mixed $value
     */
    public function canonicalizeValue( & $value , $record = null , $args = null )
    {
        $cb = $this->filter ?: $this->canonicalizer ?: null;
        if( $cb ) {
            return $value = call_user_func( $cb , $value,$record,$args);
        }
        return $value;
    }

    /**
     * For an existing record, we might need the record data to return specified valid values.
     */
    public function getValidValues( $record = null , $args = null )
    {
        if( $this->validValues ) {
            return Utils::evaluate( $this->validValues , array($record, $args) );
        } 
        elseif( $this->validValueBuilder ) {
            return Utils::evaluate( $this->validValueBuilder , array($record, $args) );
        }
    }

    public function getDefaultValue( $record = null, $args = null )
    {
        // XXX: might contains array() which is a raw sql statement.
        if( $this->default ) {
            return Utils::evaluate( $this->default , array($record, $args));
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
     *
     * @param mixed $value
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

    public function inflate( $value, $record )
    {
        if( $this->inflator ) {
            return call_user_func( $this->inflator , $value , $record );
        }
        // use global inflator
        return Inflator::inflate( $value , $this->isa );
    }


    public function display( $value )
    {
        if( $this->validPairs && isset( $this->validPairs[ $value ] ) ) {
            return $this->validPairs[ $value ];
        }

        if( $this->validValues && $validValues = Utils::evaluate($this->validValues) ) {
            // search value in validValues array
            // because we store the validValues in an (label => value) array.
            if( ArrayUtils::is_assoc_array( $validValues ) ) {
                if( false !== ($label = array_search( $value , $validValues)) ) {
                    return $label;
                }
                return;
            } elseif( in_array($value,$validValues) ) {
                return $value;
            }
        }

        if( $this->validValueBuilder && $values = call_user_func($this->validValueBuilder) ) {
            if( ArrayUtils::is_assoc_array( $values ) ) {
                if( false !== ($label = array_search($value,$values) ) ) {
                    return $label;
                }
                return;
            } elseif( in_array($value, $values ) ) {
                return $value;
            }
        }

        if( $this->isa == 'bool' )
            return $value ? _('Yes') : _('No');

        if( $value ) {
            if( is_string($value) ) {
                return _( $value );
            } 
            // quick inflator for DateTime object.
            elseif( is_a($value,'DateTime',true) ) {
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
        return ucfirst($this->name);
    }

}
