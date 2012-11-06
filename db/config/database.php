<?php return array (
  'bootstrap' => 
  array (
    0 => 'tests/bootstrap.php',
  ),
  'schema' => 
  array (
    'auto_id' => 1,
    'paths' => 
    array (
      0 => 'tests/schema',
    ),
  ),
  'cache' => 
  array (
    'class' => 'LazyRecord\\Memcache',
    'servers' => 
    array (
      0 => 
      array (
        'host' => 'localhost',
        'port' => 11211,
      ),
    ),
  ),
  'data_sources' => 
  array (
    'default' => 
    array (
      'dsn' => 'sqlite:tests.db',
      'user' => NULL,
      'pass' => NULL,
    ),
  ),
);