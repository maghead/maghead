<?php return array (
  'bootstrap' => 
  array (
    0 => 'tests/bootstrap.php',
  ),
  'schema' => 
  array (
    'auto_id' => true,
    'base_model' => '\\Lazy\\BaseModel',
    'base_collection' => '\\Lazy\\BaseCollection',
    'paths' => 
    array (
      0 => 'tests/schema',
    ),
  ),
  'data_sources' => 
  array (
    'default' => 
    array (
      'dsn' => 'pgsql:dbname=lazy_test',
      'query_options' => 
      array (
        'quote_column' => true,
        'quote_table' => true,
      ),
    ),
    'mysql' => 
    array (
      'dsn' => 'mysql:host=localhost;dbname=test',
      'user' => 'root',
      'pass' => 123123,
    ),
  ),
);