<?php 
return array( 
  'schema' => array( 
      'paths' => array( 
          'tests/schema',
        ),
    ),
  'data_sources' => array( 
      'default' => array( 
          'dsn' => 'sqlite::memory:',
        ),
      'slave' => array( 
          'dsn' => 'mysql:host=localhost;dbname=test',
          'user' => 'root',
          'pass' => 123123,
        ),
    ),
);
?>