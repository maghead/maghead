<?php
require 'vendor/autoload.php';

// PHPUnit TestCase polyfill
// This should work for HHVM on travis
if (!class_exists('\PHPUnit_Framework_TestCase') && class_exists('\PHPUnit\Framework\TestCase')) {
    class_alias('\PHPUnit\Framework\TestCase', '\PHPUnit_Framework_TestCase');
}
