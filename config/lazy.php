<?php return array (
  'bootstrap' => 
  array (
    0 => 'tests/bootstrap.php',
  ),
  'schema' => 
  array (
    'paths' => 
    array (
      0 => 'tests/schema',
    ),
  ),
  'data_sources' => 
  array (
    'default' => 
    array (
      'dsn' => 'sqlite:tests.db',
    ),
    'slave' => 
    array (
      'dsn' => 'mysql:host=localhost;dbname=test',
      'user' => 'root',
      'pass' => 123123,
    ),
  ),
);