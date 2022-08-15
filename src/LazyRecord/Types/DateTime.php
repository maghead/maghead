<?php
namespace LazyRecord\Types;
use DateTime as PHPDateTime;

class DateTime extends PHPDateTime
{
    // public function __toString() { return $this->format( PHPDateTime::ATOM ); }
    //
    // FIX mysql date time issue
    // https://stackoverflow.com/questions/42490101/invalid-datetime-format-1292-incorrect-datetime-value
    public function __toString() {
        return $this->format("Y-m-d H:i:s");
    }
}

