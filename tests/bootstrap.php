<?php
$loader = require "vendor/autoload.php";
require "tests/model_helpers.php";
require "src/PHPUnit/Framework/ModelTestCase.php";
mb_internal_encoding('UTF-8');
error_reporting( E_ALL );

use Universal\ClassLoader\BasePathClassLoader;
define('ROOT',dirname(__DIR__));
$loader = new BasePathClassLoader(array(
    ROOT . '/vendor/pear',
    ROOT . '/tests',
    ROOT . '/tests/schema',
    ROOT . '/tests/src',
));
$loader->useIncludePath(false);
$loader->register(true);
// TODO: we can initialize schema files here.
