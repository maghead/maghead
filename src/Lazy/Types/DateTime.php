<?php
namespace Lazy\Types;

class DateTime extends \DateTime
{
    public function __toString() { return $this->format( \DateTime::ATOM ); }
}


