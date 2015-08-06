<?php
namespace LazyRecord\Schema;
use Exception;
use InvalidArgumentException;
use SQLBuilder\Universal\Syntax\Column;
use ArrayIterator;
use IteratorAggregate;


/**
 * Postgresql Data Types:
 * @link http://www.postgresql.org/docs/9.1/interactive/datatype.html
 *
 * MySQL Data Types:
 * @link http://dev.mysql.com/doc/refman/5.0/en/data-types.html
 *
 *
 * Blob type:
 *
 * @link http://dev.mysql.com/doc/refman/5.0/en/blob.html (MySQL)
 * @link http://www.postgresql.org/docs/9.1/interactive/datatype-binary.html (Postgresql)
 */
class ColumnDeclare extends Column implements ColumnAccessorInterface, IteratorAggregate
{

    /**
     * @var string[]
     */
    public $locales;

    /**
     * @var array $attributes
     *
     * The default attributes for a column.
     */
    public $attributes = array();

    /**
     * @var string $name column name (id)
     */
    public function __construct($name = NULL, $type = NULL)
    {
        $this->attributeTypes = $this->attributeTypes + array(
            /* primary key */
            'primary'       => self::ATTR_FLAG,
            'size'          => self::ATTR_INTEGER,
            'autoIncrement' => self::ATTR_FLAG,
            'immutable'     => self::ATTR_FLAG,
            'unique'        => self::ATTR_FLAG, /* unique, should support by SQL syntax */
            'null'          => self::ATTR_FLAG,
            'notNull'       => self::ATTR_FLAG,
            'typeConstraint' => self::ATTR_FLAG,
            'timezone'      => self::ATTR_FLAG,
            'renderable'    => self::ATTR_FLAG,

            /* column label */
            'label' => self::ATTR_ANY,

            'desc'  => self::ATTR_STRING,

            'comment'  => self::ATTR_STRING,


            /* reference to model schema */
            'refer' => self::ATTR_STRING,

            'default' => self::ATTR_ANY,

            'validator'  => self::ATTR_ANY,

            'validatorArgs'  => self::ATTR_ANY,

            'validValues' => self::ATTR_ANY,

            'validValueBuilder' => self::ATTR_CALLABLE,

            'optionValues' => self::ATTR_ANY,

            /* contains an associative array */
            'validPairs' => self::ATTR_ANY,


            // canonicalizer
            'canonicalizer' => self::ATTR_CALLABLE,

            'virtual' => self::ATTR_FLAG,

            // an alias of canonicalizer
            'filter' => self::ATTR_CALLABLE,

            'inflator' => self::ATTR_CALLABLE,

            'deflator' => self::ATTR_CALLABLE,

            // renderAs widget
            'renderAs' => self::ATTR_STRING,

            'widgetAttributes' => self::ATTR_ARRAY,

            /* content type can be any text like 'ImageFile', 'File', 'Binary', 'Text', 'Image' */
            'contentType' => self::ATTR_STRING,

            /* primary field for CMS */
            'primaryField' => self::ATTR_FLAG,
        );
        parent::__construct($name, $type);
    }

    public function name($name) 
    {
        $this->name = $name;
        return $this;
    }


    public function localize(array $locales) {
        $this->locales = $locales;
        return $this;
    }

    /**
     * This method is an alias of "notNull"
     */
    public function required()
    {
        $this->notNull = true;
        return $this;
    }

    /**
     * serial type
     *
     * for postgresql-only
     */
    public function serial()
    {
        $this->type = 'serial';
        $this->isa = 'int';
        return $this;
    }

    public function json()
    {
        $this->type = 'text';
        $this->isa = 'json';
        return $this;
    }

    public function renderAs($renderAs,$widgetAttributes = array() ) {
        $this->renderAs = $renderAs;
        $this->widgetAttributes = $widgetAttributes;
        return $this;
    }


    /**
     * Use referenece from existing relationship 
     *
     * @param string $relationship relationship id
     */
    public function refer($relationship)
    {
        $this->attributes['refer'] = $relationship;
        return $this;
    }

    public function index($indexName = null) {
        $this->attributes['index'] = $indexName ?: true;
        return $this;
    }

    public function validator() {
        $args = func_get_args();
        if( count($args) == 1 && is_callable($args[0]) ) {
            $this->attributes['validator'] = $args[0];
            return $this;
        }
        elseif( is_string($args[0]) ) {
            $arg = $args[0];
            if( is_a($arg,'ValidationKit\Validator',true) ) {
                $this->attributes['validator'] = $args[0];
                if(isset($args[1]))
                    $this->attributes['validatorArgs'] = $args[1];
                return $this;
            }

            // guess class name
            $c = 'ValidationKit\\' . $arg;
            if( is_a($c, 'ValidationKit\\Validator',true) ) {
                $this->attributes['validator'] = $c;
                if(isset($args[1]))
                    $this->attributes['validatorArgs'] = $args[1];
                return $this;
            }

            $c = 'ValidationKit\\' . $arg . 'Validator';
            if( is_a($c, 'ValidationKit\\Validator',true) ) {
                $this->attributes['validator'] = $c;
                if(isset($args[1]))
                    $this->attributes['validatorArgs'] = $args[1];
                return $this;
            }
        }
        $this->attributes['validator'] = $args[0];
    }


    /**
     * Export column attributes to an array
     *
     * @return array
     */
    public function export()
    {
        return array(
            'name' => $this->name,
            'attributes' => array_merge(get_object_vars($this), $this->attributes),
        );
    }

    /**
     * Combine column object properties and extended attributes 
     *
     * @return array
     */
    public function toArray()
    {
        return array_merge(get_object_vars($this),$this->attributes);
    }

    public function dump()
    {
        return var_export( $this->export() , true );
    }

    public function __isset($name)
    {
        return isset( $this->attributes[ $name ] );
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

    /**
     * Which should be something like getAttribute($name)
     *
     * @param string $name attribute name
     */
    public function get($name) 
    {
        if ( isset($this->attributes[$name]) ) {
            return $this->attributes[$name];
        }
    }

    public function getLabel() {
        return _($this->get('label'));
    }

    public function getName() {
        return $this->name;
    }

    public function getDefaultValue( $record = null, $args = null )
    {
        // XXX: might contains array() which is a raw sql statement.
        if ($val = $this->get('default') ) {
            return Utils::evaluate( $val , array($record, $args));
        }
    }

    /**
     * For an existing record, we might need the record data to return specified valid values.
     */
    public function getValidValues($record = null, $args = null)
    {
        if ($validValues = $this->get('validValues')) {
            return Utils::evaluate( $validValues , array($record, $args) );
        } elseif( $builder = $this->get('validValueBuilder') ) {
            return Utils::evaluate( $builder , array($record, $args) );
        }
    }


    /**
     * Return an array iterator of extended attributes.
     *
     * TODO: consider using export() method to combine the column properties.
     */
    public function getIterator()
    {
        return new ArrayIterator($this->attributes);
    }

    /**
     * Rebless the data into RuntimeColumn object.
     *
     * @return RuntimeColumn
     */
    public function asRuntimeColumn() {
        return new RuntimeColumn($this->name, $this->attributes);
    }
}


