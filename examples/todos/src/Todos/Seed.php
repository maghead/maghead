<?php
namespace Todos;

class Seed
{
    public static function seed()
    {
        $todo = new \Todos\Model\Todo;
        $ret = $todo->create(array( 
            'title' => 'Dinner',
            'content' => 'blah blah...',
        ));
        if( ! $ret->success )
            echo $ret;
    }
}



