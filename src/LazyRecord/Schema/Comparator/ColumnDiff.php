<?php
namespace LazyRecord\Schema\Comparator;
use Closure;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\ColumnAccessorInterface;

class ColumnDiff {

    /**
     * @var string Column Name
     */
    public $name;

    /**
     * @var string Diff type (added or removed)
     */
    public $flag;

    /**
     * @var Schema Column Object
     */
    public $column;

    public $details = array();

    public function __construct($name, $flag, ColumnAccessorInterface $column)
    {
        $this->name = $name;
        $this->flag = $flag;
        $this->column = $column;
    }

    public function appendDetail(AttributeDiff $attributeDiff)
    {
        $this->details[] = $attributeDiff;
    }

    public function toColumnAttrsString() 
    {
        $line = sprintf('% 2s %-16s %-16s',$this->flag, $this->name, $this->column->type );
        $attrStrs = array();
        foreach( $this->column->attributes as $property => $value ) {
            if ( $property == "type" ) {
                continue;
            }
            if (is_object($value)) {
                if ($value instanceof Closure) {
                    $attrStrs[] = "$property:{Closure}";
                } else {
                    $attrStrs[] = "$property:" . str_replace("\n","",var_export($value,true));
                }
            }
            elseif (is_string($value)) {
                $attrStrs[] = "$property:$value";
            }
        }
        return $line . join(', ', $attrStrs);
    }
}

