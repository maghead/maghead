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
     * @param Schema $before schema before
     * @param Schema $after new schema
     */
    public function compare(SchemaInterface $before, SchemaInterface $after) 
    {
        $diff = array();

        $beforeColumns = $before ? $before->getColumns() : array();
        $afterColumns = $after ? $after->getColumns() : array();

        $columnKeys = array_unique(
            array_merge(array_keys($beforeColumns), array_keys($afterColumns) )
        );

        foreach ($columnKeys as $key) 
        {
            // If schema and db has the same column, we then compare the column definitions
            if (isset($beforeColumns[$key]) && isset($afterColumns[$key])) {
                $bc = $beforeColumns[$key];
                $ac = $afterColumns[$key];
                $afterc = $afterColumns[$key];

                $d = new ColumnDiff($key, '=', $bc, $ac);

                // compare the type info
                if ($bc->type != $ac->type) {
                    $d->appendDetail(new AttributeDiff('type', $bc->buildTypeName(), $ac->buildTypeName()));
                }

                if ($bc->length != $ac->length) {
                    $d->appendDetail(new AttributeDiff('length', $bc->buildTypeName(), $ac->buildTypeName()));
                }

                if ($bc->decimals != $ac->decimals) {
                    $d->appendDetail(new AttributeDiff('decimals', $bc->buildTypeName(), $ac->buildTypeName()));
                }

                if ($bc->unsigned != $ac->unsigned) {
                    $d->appendDetail(new AttributeDiff('unsigned', $bc->unsigned, $ac->unsigned));
                }

                // have the same column, compare attributes
                $attributes = array('default','primary');
                foreach ($attributes as $attributeName) {
                    if ($ac->{ $attributeName } != $bc->{ $attributeName }) {
                        $d->appendDetail(new AttributeDiff($attributeName , $ac->{$attributeName}, $afterc->{$attributeName}));
                    }
                }
                if (count($d->details) > 0) {
                    $diff[] = $d;
                }
            }
            elseif ( isset($beforeColumns[$key]) && ! isset($afterColumns[$key]))
            {
                // flag: -
                $diff[] = new ColumnDiff($key, '-', $beforeColumns[$key], NULL);
            }
            elseif ( isset($afterColumns[$key]) && ! isset($beforeColumns[$key]) ) 
            {
                // flag: +
                $diff[] = new ColumnDiff($key, '+', NULL, $afterColumns[$key]);
            }
        }
        return $diff;
    }

}


