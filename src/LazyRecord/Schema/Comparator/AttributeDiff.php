<?php
namespace LazyRecord\Schema\Comparator;
use Closure;
use LazyRecord\Schema\SchemaInterface;
use LazyRecord\Schema\ColumnAccessorInterface;
use LazyRecord\Schema\Comparator\ColumnDiff;
use LazyRecord\Schema\Comparator\AttributeDiff;

class AttributeDiff
{
    public $name;

    public $before;

    public $after;

    public function __construct($name, $before, $after)
    {
        $this->name = $name;
        $this->before = $before;
        $this->after = $after;
    }

    public function getBeforeDescription() {
        return sprintf("- %s %s\n", $this->name, $this->serializeVar($this->before));
    }

    public function getAfterDescription() {
        return sprintf("- %s %s\n", $this->name, $this->serializeVar($this->after));
    }

    public function serializeVar($var) {
        if (is_object($var)) {
            return get_class($var);
        } else if (is_array($var)) {
            return join(', ', $var);
        }
        return $var;
    }
}
