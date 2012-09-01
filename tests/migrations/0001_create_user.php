<?php

// do create user
class CreateUser_1346436136 extends LazyRecord\Migration\Migration
{
    public function upgrade() {
        $this->importSchema( new tests\UserSchema );
    }
}

