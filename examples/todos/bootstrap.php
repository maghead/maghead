<?php
require 'Universal/ClassLoader/BasePathClassLoader.php';
use Universal\ClassLoader\BasePathClassLoader;
$loader = new BasePathClassLoader(array(
    __DIR__ . '/src', 
    __DIR__ . '/vendor/pear',
));
$loader->useIncludePath(true);
$loader->register();
