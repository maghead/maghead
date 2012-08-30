<?php 
return array( 
  'bootstrap' => array( 
      'tests/bootstrap.php',
    ),
  'schema' => array( 
      'auto_id' => 1,
      'paths' => array( 
          'tests/schema',
        ),
    ),
  'data_sources' => array( 
      'default' => array( 
          'dsn' => 'sqlite::memory:',
          'user' => NULL,
          'pass' => NULL,
        ),
    ),
);
?>