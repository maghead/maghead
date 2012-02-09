#!/usr/bin/env php
<?php
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader(array(
    __DIR__ . '/src', 
    'vendor/pear', 
));
$loader->useIncludePath(true);
$loader->register();

$console = new Lazy\Console;
$console->run($argv);
