<?php
namespace LazyRecord\Schema;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\ColumnAccessorInterface;
use LazyRecord\Schema\Comparator\ColumnDiff;
use LazyRecord\Schema\Comparator\AttributeDiff;
use Closure;
use SQLBuilder\Raw;

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

                $d = new ColumnDiff($key, 'M', $bc, $ac);

                // compare the type info
                if (strtolower($bc->type) != strtolower($ac->type)) {
                    $d->appendDetail(new AttributeDiff('type', strtolower($bc->buildTypeName()), strtolower($ac->buildTypeName())));
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

                if ($bc->notNull != $ac->notNull) {
                    $d->appendDetail(new AttributeDiff('notNull', $bc->notNull, $ac->notNull));
                }

                if ($bc->primary != $ac->primary) {
                    $d->appendDetail(new AttributeDiff('primary', $bc->primary, $ac->primary));
                }

                // have the same column, compare attributes
                $attributes = array('default');
                foreach ($attributes as $n) {
                    // Closure are meaningless
                    if ($ac->{$n} instanceof Closure || $bc->{$n} instanceof Closure) {
                        continue;
                    }

                    $aval = $ac->{$n};
                    $bval = $bc->{$n};

                    if (is_array($aval)) {
                        $aval = new Raw($aval[0]);
                    }
                    if (is_array($bval)) {
                        $bval = new Raw($bval[0]);
                    }

                    if (($aval instanceof Raw && $bval instanceof Raw && $aval->compare($bval) != 0) || $aval != $bval) {
                        $d->appendDetail(new AttributeDiff($n , $aval, $bval));
                    }
                }
                if (count($d->details) > 0) {
                    $diff[] = $d;
                }
            }
            elseif ( isset($beforeColumns[$key]) && ! isset($afterColumns[$key]))
            {
                // flag: -
                $diff[] = new ColumnDiff($key, 'D', $beforeColumns[$key], NULL);
            }
            elseif ( isset($afterColumns[$key]) && ! isset($beforeColumns[$key]) ) 
            {
                // flag: +
                $diff[] = new ColumnDiff($key, 'A', NULL, $afterColumns[$key]);
            }
        }
        return $diff;
    }

}


