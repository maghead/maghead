<?php

namespace Maghead\Migration;

interface Downgradable
{
    public function downgrade();
}
