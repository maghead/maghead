<?php

namespace Maghead\Schema;

use DateTime;
use Maghead\Deflator;
use Maghead\Inflator;
use Maghead\ArrayUtils;
use Maghead\Utils;
use Maghead\BaseModel;
use Exception;
use ArrayIterator;
use IteratorAggregate;
use SQLBuilder\Raw;
use SQLBuilder\Driver\BaseDriver;
use Closure;

class InvalidValueTypeException extends Exception
{
}

class RuntimeColumn implements IteratorAggregate, ColumnAccessorInterface
{
    // Read only fields
    public $name;

    public $primary;

    public $unsigned;

    public $type;

    public $isa;

    public $notNull;

    public $required;

    public $default;

    public $validator;

    public $filter;

    public $canonicalizer;

    public $validValues;

    protected $attributes;

    public function __construct($name, array $attributes = array())
    {
        $this->name = $name;
        $this->attributes = $attributes;

        // predefined properties in SQLBuilder\Universal\Syntax\Column
        $this->primary = $attributes['primary'];
        $this->unsigned = $attributes['unsigned'];
        $this->type = $attributes['type'];
        $this->isa = $attributes['isa'];
        $this->notNull = $attributes['notNull'];

        if (isset($attributes['required'])) {
            $this->required = $attributes['required'];
        }
        if (isset($attributes['filter'])) {
            $this->filter = $attributes['filter'];
        }
        if (isset($attributes['canonicalizer'])) {
            $this->canonicalizer = $attributes['canonicalizer'];
        }
        if (isset($attributes['validator'])) {
            $this->validator = $attributes['validator'];
        }
        if (isset($attributes['validValues'])) {
            $this->validValues = $attributes['validValues'];
        }
        if (isset($attributes['default'])) {
            $this->default = $attributes['default'];
        }
    }

    public function getName()
    {
        return $this->name;
    }

    /**
     * For iterating attributes.
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
        return isset($this->attributes[ $name ]);
    }

    public function __get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
    }

    public function get($name)
    {
        if (isset($this->attributes[$name])) {
            return $this->attributes[$name];
        }
    }

    public function has($name)
    {
        return isset($this->attributes[ $name ]);
    }

    public function __set($n, $v)
    {
        return $this->attributes[$n] = $v;
    }

    /**
     * Canonicalize a value before updating or creating.
     *
     * The canonicalize handler takes the original value ($value), current 
     * record ($record) and the arguments ($args)
     *
     * @param mixed     $value
     * @param BaseModel $record
     * @param array     $args
     *
     * @return mixed $value
     */
    public function canonicalizeValue(&$value, $record = null, $args = null)
    {
        $cb = $this->filter ?: $this->canonicalizer ?: null;
        if ($cb) {
            if ($cb instanceof Closure) {
                return $cb($value, $record, $args);
            }

            return call_user_func($cb, $value, $record, $args);
        }

        return $value;
    }

    /**
     * For an existing record, we might need the record data to return specified valid values.
     */
    public function getValidValues($record = null, $args = null)
    {
        if ($builder = $this->validValues) {
            if ($builder instanceof Closure) {
                return $builder($record, $args);
            } elseif (is_callable($builder)) {
                return call_user_func_array($builder, array($record, $args));
            }

            return $builder;
        }
    }

    public function getOptionValues($record = null, $args = null)
    {
        if ($optionValues = $this->get('optionValues')) {
            return Utils::evaluate($optionValues, array($record, $args));
        }
    }

    public function getDefaultValue($record = null, $args = null)
    {
        // NOTE: the default value property might contain array() which was
        // designed for raw sql statement.
        if ($this->default !== null) {
            $val = $this->default;
            if ($val instanceof Closure) {
                return $val($record, $args);
            } elseif (is_callable($val)) {
                return call_user_func_array($val, array($record, $args));
            }

            return $val;
        }
    }

    /**
     * Column value type casting for input values.
     *
     * @param mixed $value referenced value
     *
     * @return mixed
     */
    public function typeCast($value)
    {
        if ($value instanceof Raw) {
            return $value;
        }
        switch ($this->isa) {
        case 'int':
            return intval($value);
        case 'str':
            return (string) $value;
        case 'bool':
            if ($value === null || $value === '') {
                return;
            }
            return filter_var($value, FILTER_VALIDATE_BOOLEAN, array('flags' => FILTER_NULL_ON_FAILURE));
        case 'DateTime':
            if ($value === '' || $value === 0 || $value === false) {
                return;
            }
        }
        return $value;
    }

    public function validateType($value)
    {
        switch ($this->isa) {
        case 'str':
            if (!is_string($value)) {
                return false;
            }
            break;
        case 'int':
            if (!is_integer($value)) {
                return false;
            }
            break;
        case 'float':
            if (!is_float($value)) {
                return false;
            }
            break;
        case 'bool':
            if (!is_bool($value)) {
                return false;
            }
            break;
        }
        return true;
    }

    /** 
     * deflate value.
     *
     * @param mixed $value
     **/
    public function deflate($value, BaseDriver $driver = null)
    {
        // run column specified deflator
        if ($f = $this->get('deflator')) {
            return call_user_func($f, $value);
        }
        // use global deflator, check self type, and do type casting
        return Deflator::deflate($value, $this->isa, $driver);
    }

    public function inflate($value, $record)
    {
        if ($f = $this->get('inflator')) {
            return call_user_func($f, $value, $record);
        }
        // use global inflator
        return Inflator::inflate($value, $this->isa);
    }

    public function display($value)
    {
        if ($this->validPairs && isset($this->validPairs[ $value ])) {
            return $this->validPairs[ $value ];
        }

        if ($this->validValues && $validValues = Utils::evaluate($this->validValues)) {
            // search value in validValues array
            // because we store the validValues in an (label => value) array.
            if (ArrayUtils::is_assoc_array($validValues)) {
                if (false !== ($label = array_search($value, $validValues))) {
                    return $label;
                }

                return;
            } elseif (in_array($value, $validValues)) {
                return $value;
            }
        }

        // Optional Values
        if ($this->optionValues && $optionValues = Utils::evaluate($this->optionValues)) {
            // search value in validValues array
            // because we store the validValues in an (label => value) array.
            if (ArrayUtils::is_assoc_array($optionValues)) {
                if (false !== ($label = array_search($value, $optionValues))) {
                    return $label;
                }

                return $value;
            } elseif (in_array($value, $optionValues)) {
                return $value;
            }

            return $value;
        }

        // backward compatible method
        if ($this->validValueBuilder && $values = call_user_func($this->validValueBuilder)) {
            if (ArrayUtils::is_assoc_array($values)) {
                if (false !== ($label = array_search($value, $values))) {
                    return $label;
                }

                return;
            } elseif (in_array($value, $values)) {
                return $value;
            }
        }

        if ($this->isa == 'bool') {
            return $value ? _('Yes') : _('No');
        }

        if ($value) {
            if (is_string($value)) {
                return _($value);
            }
            // quick inflator for DateTime object.
            elseif ($value instanceof DateTime) {
                return $value->format(DateTime::ATOM);
            }
        }

        return $value;
    }

    public function getLabel()
    {
        if ($label = $this->get('label')) {
            return _($label);
        }

        return ucfirst($this->name);
    }
}
