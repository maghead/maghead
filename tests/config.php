<?php 
return array( 
  'schema' => array( 
      'paths' => array( 
          'tests',
        ),
    ),
  'data_source' => array( 
      'default' => 'sqlite',
      'nodes' => array(
            'sqlite' => array( 'dsn' => 'sqlite::memory:',),
            'slave' => array( 
                'dsn' => 'mysql:host=localhost;dbname=test',
                'user' => 'root',
                'pass' => 123123,
            ),
        ),
    ),
);
?>
