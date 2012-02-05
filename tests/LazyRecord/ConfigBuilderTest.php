<?php

class ConfigBuilderTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $builder = new LazyRecord\ConfigBuilder;
        ok($builder);

        $builder->read('tests/lazy.yml');
        $builder->validate();
        $content = $builder->build();
        ok( $content );

        file_put_contents('tests/lazy-config.php'
                ,$content);


        $loader = new LazyRecord\ConfigLoader;
        $loader->load( 'tests/lazy-config.php');

        $conM = LazyRecord\ConnectionManager::getInstance();
        $conn = $conM->getDefault();
        ok( $conn );

        $conM->closeAll();
    }
}

