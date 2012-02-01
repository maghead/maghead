<?php
require 'vendor/pear/PHPUnit_TestMore.php';
require 'vendor/pear/Universal/ClassLoader/BasePathClassLoader.php';
$loader = new \Universal\ClassLoader\BasePathClassLoader(array('src', 'vendor/pear', 'tests/src'));
$loader->useIncludePath(true);
$loader->register();
