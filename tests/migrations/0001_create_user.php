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

        $this->addColumn('test',function($c) {
            $c->name('address');
            $c->text();
        });
    }

    public function downgrade() {
        $this->executeSql('drop table test');
        $this->executeSql('drop table users');
    }
}

