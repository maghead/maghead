<?php

namespace Maghead\TableParser;

class Token
{
    /**
     * @var string
     *
     * Token type
     */
    public $type;

    /**
     * @var mixed
     */
    public $val;

    public function __construct($type, $val)
    {
        $this->type = $type;
        $this->val = $val;
    }
}
