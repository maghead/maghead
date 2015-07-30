<?php
require 'vendor/autoload.php';
use CLIFramework\Component\Table\Table;

$dbh = new PDO('pgsql:host=localhost;dbname=testing', 'postgres');
// $dbh->query('create table authors (confirmed BOOL default false);');
$dbh->query('delete from authors');

$stm = $dbh->prepare('insert into authors (name,confirmed) values (:name, :confirmed)');
if (false === $stm->execute([':confirmed' => 0, ':name' => '0'])) {
    var_dump( $stm->errorInfo() ); 
}
$stm = $dbh->prepare('insert into authors (name,confirmed) values (:name, :confirmed)');
if (false === $stm->execute([':confirmed' => 1, ':name' => '1'])) {
    var_dump( $stm->errorInfo() ); 
}

$stm = $dbh->prepare('insert into authors (name, confirmed) values (:name,:confirmed)');
if (false === $stm->execute([':confirmed' => true, ':name' => 'true'])) {
    var_dump( $stm->errorInfo() ); 
}

$stm = $dbh->prepare('insert into authors (name, confirmed) values (:name,:confirmed)');
if (false === $stm->execute([':confirmed' => false, ':name' => 'false'])) {
    var_dump( $stm->errorInfo() ); 
}


function dumpQuery($dbh, $query)
{
    $stm = $dbh->query($query);
    if ($stm == false) {
        var_dump($dbh->errorInfo());
        return;
    }

    echo "Result for: $query\n";
    $rows = $stm->fetchAll(PDO::FETCH_ASSOC);
    $table = new Table;
    $table->setHeaders(array_keys($rows[0]));
    foreach($rows as $row) {
        $values = array_map(function($val) { return var_export($val, true); }, array_values($row));
        $table->addRow($values);
    }

    echo $table->render();
}
dumpQuery($dbh,'select * from authors WHERE confirmed is false');
dumpQuery($dbh,'select * from authors WHERE confirmed is true');
// var_dump($dbh->query('select * from authors WHERE confiremd = 0')->fetchAll());
/*
$dbh = new PDO('mysql:host=localhost;dbname=testing', 'root', 'qwer1234');
$stm = $dbh->prepare('insert into authors (name) values (:name)');
$stm->execute([ ':name' => 'John' ]);

var_dump($dbh->query('select * from authors')->fetchAll());

$stm = $dbh->prepare('update authors set name = :name');
$stm->execute([ ':name' => null ]);

var_dump($dbh->query('select * from authors')->fetchAll());

$dbh->query('delete from authors');
 */
