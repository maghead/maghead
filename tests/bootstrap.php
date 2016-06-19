<?php
$loader = require "vendor/autoload.php";
require "tests/model_helpers.php";
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
$loader->add(null,'tests');
$loader->add(null,'tests/src');
