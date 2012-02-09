<?php
# require 'vendor/pear/PHPUnit_TestMore.php';
require 'PHPUnit_TestMore.php';
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader(array(
    __DIR__ . '/src', 
    'vendor/pear', 
    'tests',
    'tests/schema',
    'tests/src'
));
$loader->register();
