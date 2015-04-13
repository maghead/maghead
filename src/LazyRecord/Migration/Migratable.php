<?php
namespace LazyRecord\Migration;

interface Migratable {

    public function upgrade();

    public function downgrade();

}



