<?php
namespace LazyRecord\Schema;
use DateTime;
use LazyRecord\Deflator;
use LazyRecord\Inflator;
use LazyRecord\ArrayUtils;
use LazyRecord\Utils;
use LazyRecord\Schema\ColumnAccessorInterface;
use Exception;
use ArrayIterator;
use IteratorAggregate;
use InvalidArgumentException;
use SQLBuilder\Raw;

class InvalidValueTypeException extends Exception { }

class RuntimeColumn implements IteratorAggregate, ColumnAccessorInterface
{
    public $name;

    public $attributes = array();

    public function __construct($name, & $attributes)
    {
        $this->name = $name;
        $this->attributes = $attributes;
    }


    public function getName() {
        return $this->name;
    }

    /**
     * For iterating attributes
     */
    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

    public static function __set_state($hash) 
    {
        return new self($hash['name'], $hash['attributes']);
    }



    public function __isset($name)
    {
        return isset( $this->attributes[ $name ] );
    }

    public function __get($name)
    {
        if ( isset($this->attributes[$name]) ) {
            return $this->attributes[$name];
        }
    }

    public function get($name) 
    {
        if ( isset($this->attributes[$name]) ) {
            return $this->attributes[$name];
        }
    }

    public function has($name)
    {
        return isset( $this->attributes[ $name ] );
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
        $cb = $this->get('filter') ?: $this->get('canonicalizer') ?: null;
        if ($cb) {
            return $value = call_user_func($cb, $value, $record, $args);
        }
        return $value;
    }

    /**
     * For an existing record, we might need the record data to return specified valid values.
     */
    public function getValidValues( $record = null , $args = null )
    {
        if( $validValues = $this->get('validValues') ) {
            return Utils::evaluate( $validValues , array($record, $args) );
        } elseif( $builder = $this->get('validValueBuilder') ) {
            return Utils::evaluate( $builder , array($record, $args) );
        }
    }

    public function getOptionValues( $record = null, $args = null )
    {
        if( $optionValues = $this->get('optionValues') ) {
            return Utils::evaluate( $optionValues , array($record, $args) );
        } 
    }

    public function getDefaultValue( $record = null, $args = null )
    {
        // XXX: might contains array() which is a raw sql statement.
        if( $val = $this->get('default') ) {
            return Utils::evaluate( $val , array($record, $args));
        }
    }


    /**
     * Column value type casting
     *
     *
     * @param mixed $value referenced value
     */
    public function typeCasting($value)
    {
        if ($value instanceof Raw) {
            return $value;
        }

        if ($isa = $this->get('isa')) {
            if( $isa === 'int' ) {
                return intval($value);
            }
            elseif( $isa === 'str' ) {
                return (string) $value;
            }
            elseif( $isa === 'bool' || $isa === 'boolean' ) {
                if (is_string($value)) {
                    if( $value == null || $value === '' ) {
                        return false;
                    } elseif( $value === '1' ) {
                        return true;
                    } elseif( $value === '0' ) {
                        return false;
                    } elseif( strncasecmp($value,'false',5) == 0 ) {
                        return false;
                    } elseif( strncasecmp($value,'true',4 ) == 0 ) {
                        return true;
                    }
                }
                return (boolean) $value;
            }
        }
        return $value;
    }

    public function checkTypeConstraint($value)
    {
        if ($isa = $this->get('isa')) {
            switch($isa) {
            case 'str':
                if (! is_string($value)) {
                    return false;
                }
                break;
            case 'int':
                if (! is_integer($value)) {
                    return false;
                }
                break;
            case 'bool':
            case 'boolean':
                if (! is_bool($value) ) {
                    return false;
                }
                break;
            }
        }
        return true;
    }

    /** 
     * deflate value 
     *
     * @param mixed $value
     **/
    public function deflate($value)
    {
        // run column specified deflator
        if( $f = $this->get('deflator') ) {
            return call_user_func($f, $value);
        }
        // use global deflator, check self type, and do type casting
        return Deflator::deflate($value , $this->get('isa'));
    }

    public function inflate($value, $record)
    {
        if( $f = $this->get('inflator') ) {
            return call_user_func($f , $value , $record );
        }
        // use global inflator
        return Inflator::inflate( $value , $this->get('isa') );
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

        // Optional Values
        if( $this->optionValues && $optionValues = Utils::evaluate($this->optionValues) ) {
            // search value in validValues array
            // because we store the validValues in an (label => value) array.
            if( ArrayUtils::is_assoc_array( $optionValues ) ) {
                if( false !== ($label = array_search( $value , $optionValues)) ) {
                    return $label;
                }
                return $value;
            } elseif( in_array($value,$optionValues) ) {
                return $value;
            }
            return $value;
        }

        // backward compatible method
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

        if ( $this->get('isa') == 'bool' ) {
            return $value ? _('Yes') : _('No');
        }

        if( $value ) {
            if( is_string($value) ) {
                return _( $value );
            } 
            // quick inflator for DateTime object.
            elseif ( $value instanceof DateTime) {
                return $value->format( DateTime::ATOM );
            }
        }
        return $value;
    }

    public function getLabel()
    {
        if( $label = $this->get('label') ) {
            return _( $label );
        }
        return ucfirst($this->name);
    }

}
