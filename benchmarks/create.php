<?php
require( 'tests/bootstrap.php');
require 'tests/schema/tests/Author.php';

$bench = new SimpleBench;
$bench->setN( 500000 );
$bench->setTitle('obj create');

$bench->iterate( 'obj create' , 'object construction time' , function() {
    $s = new stdClass;
});

$bench->iterate( 'model create', 'model create', function() {
    $a = new tests\Author;
});

$result = $bench->compare();
echo $result->output('console');
