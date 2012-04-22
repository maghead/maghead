#!/usr/bin/env php
<?php
require dirname(__DIR__) . '/vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader(array(
    dirname(__DIR__) . '/src', 
    dirname(__DIR__) . '/vendor/pear', 
));
$loader->useIncludePath(true);
$loader->register();

$console = new LazyRecord\Console;
$console->run($argv);
