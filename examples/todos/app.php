<?php
require '../../vendor/autoload.php';

use Maghead\ConfigLoader;
use Maghead\Bootstrap;

use Todos\Model\Todo;

$config = ConfigLoader::loadFromFile('db/config/database.yml');
Bootstrap::setup($config);

$titles = [
    ['title' => 'Attend the design meeting'],
    ['title' => 'Buy some fruits'],
    ['title' => 'Fix the bugs'],
];

foreach ($titles as $title) {
    $ret = Todo::create([
        'title' => 'Attend the design meeting'
    ]);
    if ($ret->error) {
        echo $ret->message , "\n";
        var_dump($ret);
    }
}

$todos = new Todos\Model\TodoCollection;
foreach ($todos as $todo ) {
    echo $todo->id , ' - ' , $todo->title, "\n";
}
