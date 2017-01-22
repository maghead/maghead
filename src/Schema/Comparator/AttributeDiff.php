<?php

namespace Maghead\Schema\Comparator;

use SQLBuilder\Raw;

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

    public function getBeforeDescription()
    {
        return sprintf("- %s %s\n", $this->name, $this->serializeVar($this->before));
    }

    public function getAfterDescription()
    {
        return sprintf("+ %s %s\n", $this->name, $this->serializeVar($this->after));
    }

    public function serializeVar($var)
    {
        if ($var instanceof Raw) {
            return $var->__toString();
        } elseif (is_object($var)) {
            return get_class($var);
        } elseif (is_array($var)) {
            return implode(', ', $var);
        } else {
            return var_export($var, true);
        }

        return $var;
    }
}
