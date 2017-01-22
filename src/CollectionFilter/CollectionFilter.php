<?php

namespace Maghead\CollectionFilter;

use Closure;
use Exception;
use Maghead\BaseCollection;

class CollectionFilter
{
    /**
     * constants for valid value type.
     */
    const Integer = 0;
    const String = 1;
    const Boolean = 2;
    const Float = 3;
    const DateTime = 4;

    /**
     * constants for filter condition types.
     */
    const Equal = 0;
    const Greater = 1;
    const Lesser = 2;
    const Contains = 3;
    const StartsWith = 4;
    const EndsWith = 5;
    const InSet = 6;
    const Range = 7;

    public $collection;

    /**
     * @var Maghead\Schema the schema object
     */
    public $schema;

    /**
     * @var validValues contains field-to-definitions data structure:
     *
     *    "field_name" => [ 1,2,3,4 ]
     *    "field_name" => [ "foo", "bar" ]
     *    "field_name" => [ "label" => 1, "label2" => 2]
     */
    public $validValues = array();

    public $validFields = array();

    public function __construct(BaseCollection $collection)
    {
        $this->collection = $collection;
        $this->schema = $collection->getSchema();
    }

    public function setCollection($c)
    {
        $this->collection = $c;
    }

    public function getCollection($c)
    {
        return $this->collection;
    }

    public function defineEqual($fieldName, $validValues = null)
    {
        $this->validFields[$fieldName] = self::Equal;
        if ($validValues) {
            $this->validValues[$fieldName] = $validValues;
        }
    }

    public function defineRange($field, $validValues = null)
    {
        $this->validFields[$field] = self::Range;
        if ($validValues) {
            $this->validValues[$field] = $validValues;
        }
    }

    public function defineContains($field)
    {
        $this->validFields[$field] = self::Contains;
        $this->validValues[$field] = self::String;
    }

    public function defineStartsWith($field)
    {
        $this->validFields[$field] = self::StartsWith;
        $this->validValues[$field] = self::String;
    }

    public function defineEndsWith($field)
    {
        $this->validFields[$field] = self::EndsWith;
        $this->validValues[$field] = self::String;
    }

    public function defineInSet($field, $validValues)
    {
        $this->validFields[$field] = self::InSet;
        $this->validValues[$field] = $validValues;
    }

    public function validateValue($validValues, &$val)
    {
        if (is_array($validValues)) {
            if (isset($validValues[0])) {
                return in_array($val, $validValues);
            } else {
                $values = array_values($validValues);

                return in_array($val, $values);
            }
        } elseif ($validValues instanceof Closure) {
            $validValues->bindTo($this);

            return $validValues($val);
        } else {
            switch ($validValues) {
            case self::Integer:
                if (is_int($val)) {
                    return true;
                }
                if (is_numeric($val)) {
                    $val = intval($val);

                    return true;
                }

                return false;
            case self::String:
                return is_string($val);
            case self::Boolean:
                if (is_bool($val)) {
                    return true;
                }
                if ($val == '0' || $val == '1' || $val == 'true' || $val == 'false') {
                    $val = boolval($val);

                    return true;
                }

                return false;
            case self::Float:
                if (is_float($val)) {
                    return true;
                }

                // we should validate float format
                if (is_numeric($val)) {
                    $val = floatval($val);

                    return true;
                }

                return false;
            default:
                return true;
            }
        }

        return true;
    }

    public function apply(array $args)
    {
        $c = $this->collection;
        foreach ($this->validFields as $fieldName => $t) {
            if (!isset($args[$fieldName])) {
                continue;
            }

            $requestValues = (array) $args[$fieldName];

            // Conditions that takes array
            if ($t == self::Range) {
                if (count($requestValues) != 2) {
                    throw new Exception('require 2 request values for the range filter.');
                }
                $c->where()->group()->between($fieldName, $requestValues[0], $requestValues[1])->ungroup();
                continue;
            }
            if ($t == self::InSet) {
                $c->where()->in($fieldName, $requestValues);
                continue;
            }

            $where = $c->where();
            $hasParams = false;
            foreach ($requestValues as $idx => $requestValue) {
                if (isset($this->validValues[$fieldName])) {
                    $validValues = $this->validValues[$fieldName];
                    if (!$this->validateValue($validValues, $requestValue)) {
                        continue;
                    }
                }
                if ($idx == 0) {
                    $where->group();
                }

                $hasParams = true;
                switch ($t) {
                case self::Contains:
                    $where->or()->like($fieldName, '%'.$requestValue.'%');
                    break;
                case self::StartsWith:
                    $where->or()->like($fieldName, $requestValue.'%');
                    break;
                case self::EndsWith:
                    $where->or()->like($fieldName, '%'.$requestValue);
                    break;
                case self::Greater:
                    $where->or()->greaterThan($fieldName, $requestValue);
                    break;
                case self::Lesser:
                    $where->or()->lesserThan($fieldName, $requestValue);
                    break;
                case self::Equal:
                    $where->or()->equal($fieldName, $requestValue);
                    break;
                }
            }
            if ($hasParams) {
                $expr->ungroup();
            }
        }

        return $c;
    }

    /**
     * Apply filters from request parameters.
     *
     * @param string
     */
    public function applyFromRequest($requestPrefix = '_filter_')
    {
        $args = array();
        foreach ($this->validFields as $fieldName => $t) {
            if (isset($_REQUEST[ $requestPrefix.$fieldName ])) {
                $args[ $fieldName ] = $_REQUEST[ $requestPrefix.$fieldName ];
            }
        }

        return $this->apply($args);
    }
}
