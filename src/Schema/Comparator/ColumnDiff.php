<?php

namespace Maghead\Schema\Comparator;

use Closure;
use Maghead\Schema\ColumnAccessorInterface;
use LogicException;

class ColumnDiff
{
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
    public $before;

    /**
     * @var Schema Column Object
     */
    public $after;

    public $details = array();

    public function __construct($name, $flag, ColumnAccessorInterface $before = null, ColumnAccessorInterface $after = null)
    {
        $this->name = $name;
        $this->flag = $flag;
        $this->before = $before;
        $this->after = $after;

        if (!$before && !$after) {
            throw new LogicException('You must provide either {before} column or {after} column');
        }
    }

    public function getAfterColumn()
    {
        return $this->after;
    }

    public function getBeforeColumn()
    {
        return $this->before;
    }

    public function getAfterOrBeforeColumn()
    {
        return $this->after ?: $this->before;
    }

    public function appendDetail(AttributeDiff $attributeDiff)
    {
        $this->details[] = $attributeDiff;
    }

    public function toColumnAttrsString()
    {
        $column = $this->getAfterOrBeforeColumn();
        $line = sprintf('%s %-16s %-16s', $this->flag, $this->name, $column->type);
        $attrStrs = array();

        if (!empty($column->attributes)) {
            foreach ($column->attributes as $property => $value) {
                if ($property == 'type') {
                    continue;
                }
                if (is_object($value)) {
                    if ($value instanceof Closure) {
                        $attrStrs[] = "$property:{Closure}";
                    } else {
                        $attrStrs[] = "$property:".str_replace("\n", '', var_export($value, true));
                    }
                } elseif (is_string($value)) {
                    $attrStrs[] = "$property:$value";
                }
            }
        }

        return $line.implode(', ', $attrStrs);
    }
}
