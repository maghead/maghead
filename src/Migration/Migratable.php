<?php

namespace Maghead\Migration;

interface Migratable
{
    public function upgrade();

    public function downgrade();
}
