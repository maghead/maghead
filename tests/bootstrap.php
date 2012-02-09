<?php
# require 'vendor/pear/PHPUnit_TestMore.php';
require 'PHPUnit_TestMore.php';
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader(array(
    dirname(__DIR__) . '/src', 
    dirname(__DIR__) . '/vendor/pear',
    dirname(__DIR__) . '/tests',
    'tests/schema',
    'tests/src'
));
$loader->register();
