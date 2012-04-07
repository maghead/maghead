<?php
namespace LazyRecord\Schema;

class ColumnDiff {
    public $name;
    public $flag;
    public $column;
    public $attrDiffs = array();

    public function __construct($name,$flag,$column)
    {
        $this->name = $name;
        $this->flag = $flag;
        $this->column = $column;
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
    function compare( $a, $b ) 
    {
        $diff = array();

        $aColumns = $a->getColumns();
        $bColumns = $b->getColumns();

        $columnKeys = array_unique(
            array_merge(array_keys($aColumns), array_keys($bColumns) )
        );
        foreach( $columnKeys as $key ) {
            if( isset($aColumns[$key]) && isset($bColumns[ $key ] ) ) {
                
                // have the same column, compare attributes
                $attributes = array('type','isa','default','label');
                $ac = $aColumns[$key];
                $bc = $bColumns[$key];
                $d = new ColumnDiff( $key, '=', $bc );
                foreach( $attributes as $attributeName ) {
                    if( $ac->{ $attributeName } === $bc->{ $attributeName } ) {
                        // is equal
                    }
                    else if( $ac->{ $attributeName } != $bc->{ $attributeName } ) {
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


