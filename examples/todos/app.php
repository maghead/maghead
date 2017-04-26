<?php
require '../../vendor/autoload.php';

use Maghead\ConfigLoader;
use Maghead\Bootstrap;

use Todos\Model\Todo;
use Todos\Model\TodoCollection;

$config = ConfigLoader::loadFromFile('db/config/database.yml');
Bootstrap::setup($config);

// Use delete query to delete the previous records
Todo::repo()->delete()->execute();

// same as ::repo("master") or ::repo()
Todo::masterRepo()->delete()->execute();

$titles = [
    'Attend the design meeting',
    'Buy some fruits',
    'Fix the bugs',
];

foreach ($titles as $title) {
    $ret = Todo::create([ 'title' => $title ]);
    if ($ret->error) {
        echo $ret->message , "\n";
        var_dump($ret);
    }
}

$todos = new TodoCollection;
$todos->first()->update([ 'done' => true ]);

echo "### Finished todos:\n";
$todos = new TodoCollection;
$todos->where()->is('done', true);
foreach ($todos as $todo ) {
    echo '- ' , $todo->title, "\n";
}

echo "### Waiting todos\n";
$todos = new TodoCollection;
$todos->where()->is('done', false);
foreach ($todos as $todo ) {
    echo '- ' , $todo->title, "\n";
}



