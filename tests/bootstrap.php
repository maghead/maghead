<?php

$loader = require "vendor/autoload.php";
require "tests/model_helpers.php";
mb_internal_encoding('UTF-8');
error_reporting( E_ALL );
if (extension_loaded('xhprof') ) {
    ini_set('xhprof.output_dir','/tmp');
}
$loader->add(null,'tests');
$loader->add(null,'tests/src');

use LazyRecord\Schema\SchemaGenerator;
use LazyRecord\ConfigLoader;
use CLIFramework\Logger;

$config = ConfigLoader::getInstance();
$config->loadFromArray(array( 
    'bootstrap' => array ('tests/bootstrap.php'),
    'schema' => array(
        'auto_id' => 1,
        'paths' => array('tests/TestApp'),
    ),
    'data_sources' =>
    array (
        'default' =>
            array (
                'dsn' => 'sqlite::memory:',
                'user' => NULL,
                'pass' => NULL,
            ),
        'pgsql' =>
            array (
                'dsn' => 'pgsql:host=localhost;dbname=testing',
                'user' => 'postgres',
            ),
    ),
));

$logger = new Logger;
$logger->setQuiet();

// build schema class files
$schemas = array(
    new \TestApp\Model\UserSchema,
    new \TestApp\Model\IDNumberSchema,
    new \TestApp\Model\NameSchema,
    new \AuthorBooks\Model\AddressSchema,
    new \AuthorBooks\Model\BookSchema,
    new \AuthorBooks\Model\AuthorSchema,
    new \AuthorBooks\Model\AuthorBookSchema,
    new \AuthorBooks\Model\PublisherSchema,
);
$g = new \LazyRecord\Schema\SchemaGenerator($config, $logger);
$g->setForceUpdate(true);
$g->generate($schemas);
