<?php

namespace Maghead\Schema;

interface ColumnAccessorInterface
{
    public function getName();

    public function getLabel();

    public function get($name);

    public function getDefaultValue($record = null, $args = null);
}
