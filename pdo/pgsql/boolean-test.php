<?php
require 'vendor/autoload.php';
require __DIR__ . '/../utils.php';

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


dumpQuery($dbh,'select * from authors WHERE confirmed is false');
dumpQuery($dbh,'select * from authors WHERE confirmed = false');
dumpQuery($dbh,'select * from authors WHERE confirmed is true');
dumpQuery($dbh,'select * from authors WHERE confirmed = true');
