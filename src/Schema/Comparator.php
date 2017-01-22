<?php

namespace Maghead\Schema;

use Maghead\Schema\Comparator\ColumnDiff;
use Maghead\Schema\Comparator\AttributeDiff;
use SQLBuilder\Driver\BaseDriver;
use SQLBuilder\Driver\MySQLDriver;
use Closure;
use SQLBuilder\Raw;

class Comparator
{
    protected $driver;

    public function __construct(BaseDriver $driver)
    {
        $this->driver = $driver;
    }

    /**
     * compare two schemas.
     *
     * @param Schema $before schema before
     * @param Schema $after  new schema
     */
    public function compare(SchemaInterface $before, SchemaInterface $after)
    {
        $diff = array();

        $beforeColumns = $before ? $before->getColumns() : array();
        $afterColumns = $after ? $after->getColumns() : array();

        $columnKeys = array_unique(
            array_merge(array_keys($beforeColumns), array_keys($afterColumns))
        );

        foreach ($columnKeys as $key) {
            // If schema and db has the same column, we then compare the column definitions
            if (isset($beforeColumns[$key]) && isset($afterColumns[$key])) {
                $bc = $beforeColumns[$key];
                $ac = $afterColumns[$key];
                $afterc = $afterColumns[$key];

                $d = new ColumnDiff($key, 'M', $bc, $ac);

                // Compare the type info
                if (strtolower($bc->type) !== strtolower($ac->type)) {
                    $d->appendDetail(new AttributeDiff('type', strtolower($bc->buildTypeName($this->driver)), strtolower($ac->buildTypeName($this->driver))));
                }

                if ($bc->length !== $ac->length) {
                    $d->appendDetail(new AttributeDiff('length', $bc->buildTypeName($this->driver), $ac->buildTypeName($this->driver)));
                }

                if ($bc->decimals !== $ac->decimals) {
                    $d->appendDetail(new AttributeDiff('decimals', $bc->buildTypeName($this->driver), $ac->buildTypeName($this->driver)));
                }

                if ($bc->primary !== $ac->primary) {
                    $d->appendDetail(new AttributeDiff('primary', $bc->primary, $ac->primary));
                }

                // we only compare unsigned when:
                //   driver is MySQL or the column is not a primary key
                if ($this->driver instanceof MySQLDriver) {
                    if (!$ac->primary && !$bc->primary) {
                        if ($bc->unsigned != $ac->unsigned) {
                            $d->appendDetail(new AttributeDiff('unsigned', $bc->unsigned, $ac->unsigned));
                        }
                    }
                }

                if ($bc->notNull != $ac->notNull) {
                    $d->appendDetail(new AttributeDiff('notNull', $bc->notNull, $ac->notNull));
                }

                // They are the same column, let's compare these attributes
                $attributes = array('default');
                foreach ($attributes as $n) {
                    // Closure are meaningless
                    $aval = $ac->{$n};
                    $bval = $bc->{$n};
                    if ($aval instanceof Closure || $bval instanceof Closure) {
                        continue;
                    }
                    if (($aval === null && $bval === null) || ($aval === false && $bval === false)) {
                        continue;
                    }

                    if (is_array($aval)) {
                        $aval = new Raw($aval[0]);
                    }
                    if (is_array($bval)) {
                        $bval = new Raw($bval[0]);
                    }

                    if (($aval instanceof Raw && $bval instanceof Raw && $aval->compare($bval) != 0)) {
                        $d->appendDetail(new AttributeDiff($n, $aval, $bval));
                    } elseif (is_scalar($aval) && is_scalar($bval) && $aval != $bval) {
                        $d->appendDetail(new AttributeDiff($n, $aval, $bval));
                    }
                }
                if (count($d->details) > 0) {
                    $diff[] = $d;
                }
            } elseif (isset($beforeColumns[$key]) && !isset($afterColumns[$key])) {
                // flag: -
                $diff[] = new ColumnDiff($key, 'D', $beforeColumns[$key], null);
            } elseif (isset($afterColumns[$key]) && !isset($beforeColumns[$key])) {
                // flag: +
                $diff[] = new ColumnDiff($key, 'A', null, $afterColumns[$key]);
            }
        }

        return $diff;
    }
}
