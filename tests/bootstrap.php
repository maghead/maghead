<?php
require 'PHPUnit/TestMore.php';
require 'Universal/ClassLoader/BasePathClassLoader.php';
require 'tests/model_helpers.php';
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


// TODO: we can initialize schema files here.

