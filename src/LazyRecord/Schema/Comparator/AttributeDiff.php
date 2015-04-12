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
}
