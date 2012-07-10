<?php
require 'PHPUnit/TestMore.php';
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
require 'tests/helpers.php';
mb_internal_encoding('UTF-8');
error_reporting( E_ALL );
use Universal\ClassLoader\BasePathClassLoader;
$loader = new BasePathClassLoader(array(
    dirname(__DIR__) . '/src', 
    dirname(__DIR__) . '/vendor/pear',
    dirname(__DIR__) . '/tests',
    dirname(__DIR__) . '/tests/schema',
    dirname(__DIR__) . '/tests/src',
));
$loader->useIncludePath(true);
$loader->register();
