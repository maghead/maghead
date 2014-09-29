<?php
namespace LazyRecord\Types;
use DateTime as PHPDateTime;

class DateTime extends PHPDateTime
{
    public function __toString() { return $this->format( PHPDateTime::ATOM ); }
}

