<?php

namespace LazyRecord\Migration;

interface Downgradable
{
    public function downgrade();
}
