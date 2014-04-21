<?php
namespace LazyRecord;
use Closure;

class CollectionFilter
{

    /**
     * constants for valid value type
     */
    const Integer = 0;
    const String = 1;
    const Boolean = 2;
    const Float = 3;


    /**
     * constants for filter condition types
     */
    const Equal = 0;
    const Contains = 1;
    const StartsWith = 2;
    const EndsWith = 2;
    const InSet = 3;


    public $collection;


    /**
     * @var LazyRecord\Schema the schema object
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

    public function __construct($collection) {
        $this->collection = $collection;
        $this->schema = $collection->getSchema();
    }

    public function setCollection($c) {
        $this->collection = $c;
    }

    public function getCollection($c) {
        return $this->collection;
    }

    public function defineEqual($fieldName, $validValues = null) {
        $this->validFields[$fieldName] = self::Equal;
        if ( $validValues ) {
            $this->validValues[$fieldName] = $validValues;
        }
    }

    public function defineContains($field) {
        $this->validFields[$field] = self::Contains;
        $this->validValues[$field] = self::String;
    }

    public function defineStartsWith($field) {
        $this->validFields[$field] = self::StartsWith;
        $this->validValues[$field] = self::String;
    }

    public function defineEndsWith($field) {
        $this->validFields[$field] = self::EndsWith;
        $this->validValues[$field] = self::String;
    }

    public function defineInSet($field, $validValues = null) {
        $this->validFields[$field] = self::InSet;
        if ( $validValues ) {
            $this->validValues[$field] = $validValues;
        }
    }


    public function validateValues($validValues, & $val) {
        if ( is_array($validValues) ) {
            if ( isset($validValues[0]) ) {
                return in_array($requestValue, $validValues);
            } else {
                $values = array_values($validValues);
                return in_array($requestValue, $values);
            }
        } elseif ( $validValues instanceof Closure ) {
            $validValues->bindTo($this);
            return $validValues($val);
        } else {
            switch($validValues) {
            case self::Integer:
                if ( is_int($val) ) {
                    return true;
                }
                if ( is_numeric($val) ) {
                    $val = intval($val);
                    return true;
                }
                return false;
            case self::String:
                return is_string($val);
            case self::Boolean:
                if ( is_bool($val) ) {
                    return true;
                }
                if ( $val == '0' || $val == '1' || $val == 'true' || $val == 'false' ) {
                    $val = boolval($val);
                    return true;
                }
                return false;
            case self::Float:
                if ( is_float($val) ) {
                    return true;
                }

                // we should validate float format
                if ( is_numeric($val) ) {
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

    public function applyFromRequest() {
        $c = $this->collection;
        foreach( $this->validFields as $fieldName => $t ) {
            $requestValue = null; // XXX:

            if ( isset($this->validValues[$fieldName]) ) {
                $validValues = $validValues[$fieldName];
            } else {
                $validValues = null;
            }

            if ( $validValues ) {

                // check valid values
            }



            switch($t) {
            case self::Contains:
                $c->where()->like($fieldName, '%' . $requestValue . '%');
                break;
            case self::Equal:
                $c->where()->equal($fieldName, $requestValue);
                break;
            }
        }
        $_REQUEST;

    }

}




