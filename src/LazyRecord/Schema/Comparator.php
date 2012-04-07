<?php
namespace LazyRecord\Schema;

class ColumnDiff {
    public $name;
    public $flag;
    public $column;
}

class AttributeDiff {
    public $name;
    public $flag;
    public $attribute;
}

class Comparator
{

    static function compare( $a, $b ) 
    {
        $diff = array();

        $aColumns = $a->getColumns();
        $bColumns = $b->getColumns();

        $columnKeys = array_merge(array_keys($aColumns) , array_keys($bColumns));

        foreach( $columnKeys as $key ) {
            if( isset($aColumns[$key]) && isset($bColumns[ $key ] ) ) {
                // have the same column, compare attributes
                $attributes = array('type','isa','default','label');
                foreach( $attributes as $attributeName ) {

                }
            }
            elseif( isset($aColumns[$key]) ) 
            {

            }
            elseif( isset($bColumns[$key]) ) 
            {

            }
        }

    }



}


