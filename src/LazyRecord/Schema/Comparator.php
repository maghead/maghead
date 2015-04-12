<?php
namespace LazyRecord\Schema;
use Closure;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\ColumnAccessorInterface;
use LazyRecord\Schema\Comparator\ColumnDiff;
use LazyRecord\Schema\Comparator\AttributeDiff;

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

        foreach ($columnKeys as $key) 
        {
            // If schema and db has the same column, we then compare the column definitions
            if (isset($aColumns[$key]) && isset($bColumns[$key])) {
                
                // have the same column, compare attributes
                $attributes = array('type','default','primary','label');
                $ac = $aColumns[$key];
                $bc = $bColumns[$key];
                $d = new ColumnDiff($key, '=', $bc );
                foreach ($attributes as $attributeName) {
                    if ($ac->{ $attributeName } === $bc->{ $attributeName }) {
                        // is equal
                    } else if ( $ac->{ $attributeName } != $bc->{ $attributeName } 
                        && is_string($bc->{ $attributeName }) 
                        && is_integer($bc->{ $attributeName }) ) 
                    {
                        $d->appendDetail(new AttributeDiff(
                            $attributeName , 
                            $ac->{$attributeName},
                            $bc->{$attributeName}
                        ));
                    }
                }
                if (count($d->details) > 0) {
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


