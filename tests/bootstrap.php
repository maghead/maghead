<?php
require 'PHPUnit/TestMore.php';
require_once 'Universal/ClassLoader/BasePathClassLoader.php';
require_once 'tests/model_helpers.php';
mb_internal_encoding('UTF-8');
error_reporting( E_ALL );
use Universal\ClassLoader\BasePathClassLoader;

define('ROOT',dirname(__DIR__));

$loader = new BasePathClassLoader(array(
    ROOT . '/src', 
    ROOT . '/vendor/pear',
    ROOT . '/tests',
    ROOT . '/tests/schema',
    ROOT . '/tests/src',
));
$loader->useIncludePath(true);
$loader->register();

// TODO: we can initialize schema files here.

