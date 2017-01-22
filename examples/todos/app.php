<?php
require 'bootstrap.php';
require 'Maghead/ConfigLoader.php';

$dbLoader = new Maghead\ConfigLoader;
$dbLoader->load( __DIR__ . '/.lazy.yml');
$dbLoader->init();

$todo = new Todos\Model\Todo;
$ret = $todo->create(array( 
    'title' => 'Sample A'
));

if( ! $ret->success )
    echo $ret;
echo $ret->message , "\n";

$todos = new Todos\Model\TodoCollection;
foreach( $todos as $todo ) {
    echo $todo->id , ' - ' , $todo->title, "\n";
}


// for more details, please see documentation in doc/
