<?php
require 'vendor/autoload.php';

// This should work for HHVM on travis
if (!class_exists('PHPUnit\\Framework\\TestCase')) {
    class_alias('PHPUnit_Framework_TestCase', 'PHPUnit\\Framework\\TestCase');
}
