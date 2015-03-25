<?php
namespace LazyRecord\Schema;
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

    public $attrDiffs = array();

    public function __construct($name, $flag, ColumnAccessorInterface $column)
    {
        $this->name = $name;
        $this->flag = $flag;
        $this->column = $column;
    }

    public function toColumnAttrsString() {
        $line = sprintf('%s %-16s %-16s',$this->flag , $this->name, $this->column->type );
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

class Comparator
{


    /**
     * compare two schemas
     *
     * @param Schema $a old schema 
     * @param Schema $b new schema
     */
    public function compare(SchemaInterface $a, SchemaInterface $b) 
    {
        $diff = array();

        $aColumns = $a ? $a->getColumns() : array();
        $bColumns = $b ? $b->getColumns() : array();

        $columnKeys = array_unique(
            array_merge(array_keys($aColumns), array_keys($bColumns) )
        );
        foreach( $columnKeys as $key ) {
            if( isset($aColumns[$key]) && isset($bColumns[ $key ] ) ) {
                
                // have the same column, compare attributes
                $attributes = array('type','default','primary','label');
                $ac = $aColumns[$key];
                $bc = $bColumns[$key];
                $d = new ColumnDiff( $key, '=', $bc );
                foreach( $attributes as $attributeName ) {
                    if( $ac->{ $attributeName } === $bc->{ $attributeName } ) {
                        // is equal
                    }
                    else if( $ac->{ $attributeName } != $bc->{ $attributeName } 
                        && is_string($bc->{ $attributeName }) 
                        && is_integer($bc->{ $attributeName }) ) 
                    {
                        $d->attrDiffs[] = (object) array( 
                            'name' => $attributeName , 
                            'before' => $ac->{ $attributeName },
                            'after'  => $bc->{ $attributeName },
                        );
                    }
                }
                if(count($d->attrDiffs) > 0) {
                    $diff[] = $d;
                }
            }
            elseif( isset($aColumns[$key]) && ! isset($bColumns[$key]) ) 
            {
                // flag: -
                $diff[] = new ColumnDiff($key,'-',$aColumns[$key]);
            }
            elseif( isset($bColumns[$key]) && ! isset($aColumns[$key]) ) 
            {
                // flag: +
                $diff[] = new ColumnDiff($key,'+',$bColumns[$key]);
            }
        }
        return $diff;
    }

}


