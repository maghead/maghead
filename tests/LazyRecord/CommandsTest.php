<?php

class CommandsTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $app = new LazyRecord\Console;
        ok($app);

        ok( $app->createCommand('LazyRecord\Command\BuildConfCommand') );
        ok( $app->createCommand('LazyRecord\Command\BuildSchemaCommand') );
        ok( $app->createCommand('LazyRecord\Command\BuildBasedataCommand') );
        ok( $app->createCommand('LazyRecord\Command\PrepareCommand') );
        ok( $app->createCommand('LazyRecord\Command\InitCommand') );
        ok( $app->createCommand('LazyRecord\Command\CreateDBCommand') );
        ok( $app->createCommand('LazyRecord\Command\MigrateCommand') );
        ok( $app->createCommand('LazyRecord\Command\SchemaCommand') );
        ok( $app->createCommand('LazyRecord\Command\DiffCommand') );
    }
}

