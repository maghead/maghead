<?php

// do create user
class CreateUser_1346436136 extends LazyRecord\Migration\Migration
{
    public function upgrade() {
        $this->importSchema( new tests\UserSchema );

        $this->createTable(function($s) {
            $s->table('test');
            $s->column('foo')
                ->notNull()
                ->varchar(128);
        });
    }
}

