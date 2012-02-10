<?php

class ConfigBuilderTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $builder = new Lazy\ConfigBuilder;
        ok($builder);

        $builder->read('tests/lazy.yml');
        $builder->validate();
        $content = $builder->build();
        ok( $content );

        file_put_contents('tests/lazy-config.php'
                ,$content);


        $loader = new Lazy\ConfigLoader;
        $loader->loadConfig( 'tests/lazy-config.php');

        $loader->loadBootstrap();
        $loader->loadDataSources();

        $conM = Lazy\ConnectionManager::getInstance();
        $conn = $conM->getDefault();
        ok( $conn );

        $conM->closeAll();
    }
}

