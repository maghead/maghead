<?php
namespace LazyRecord\Schema;

class ColumnDiff {
    public $name;
    public $flag;
    public $column;

    public function __construct($name,$flag,$column)
    {
        $this->name = $name;
        $this->flag = $flag;
        $this->column = $column;
    }
}

class AttributeDiff {
    public $name;
    public $flag;
    public $attribute;
}

class Comparator
{


    /**
     * compare two schemas
     *
     * @param Schema $a old schema 
     * @param Schema $b new schema
     */
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
                // flag: -
                $diff[] = new ColumnDiff($key,'-',$column);
            }
            elseif( isset($bColumns[$key]) ) 
            {
                // flag: +
                $diff[] = new ColumnDiff($key,'+',$column);
            }
        }
        return $diff;
    }

}


