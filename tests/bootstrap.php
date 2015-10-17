<?php
$loader = require "vendor/autoload.php";
require "tests/model_helpers.php";
mb_internal_encoding('UTF-8');
error_reporting(E_ALL);
$loader->add(null,'tests');
$loader->add(null,'tests/src');

use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ConfigLoader;
use CLIFramework\Logger;

$config = ConfigLoader::getInstance();
$config->loadFromSymbol(true);
$config->initForBuild();

$logger = new Logger;
$logger->quiet();
$logger->info("Building schema class files...");

// build schema class files
$schemas = array(
    new \AuthorBooks\Model\AddressSchema,
    new \AuthorBooks\Model\AuthorBookSchema,
    new \AuthorBooks\Model\AuthorSchema,
    new \AuthorBooks\Model\BookSchema,
    new \AuthorBooks\Model\PublisherSchema,
    new \AuthorBooks\Model\TagSchema,
    new \MetricApp\Model\MetricValueSchema,
    new \PageApp\Model\PageSchema,
    new \StoreApp\Model\StoreSchema,
    new \TestApp\Model\EdmSchema,
    new \TestApp\Model\IDNumberSchema,
    new \TestApp\Model\NameSchema,
    new \TestApp\Model\PostSchema,
    new \TestApp\Model\TableSchema,
    new \TestApp\Model\UserSchema,
    new \TestApp\Model\WineCategorySchema,
    new \TestApp\Model\WineSchema,
);
$g = new \LazyRecord\Schema\SchemaGenerator($config, $logger);
$g->setForceUpdate(true);
$g->generate($schemas, true);
// $logger->info("Starting tests...");
