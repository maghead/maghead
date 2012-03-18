<?php

class ConfigBuilderTest extends PHPUnit_Framework_TestCase
{
    function test()
    {
        $builder = new LazyRecord\ConfigBuilder;
        ok($builder);

        $builder->read('tests/lazy.yml');
        $content = $builder->build();
        ok( $content );

        file_put_contents('tests/lazy.php'
                ,$content);


        $loader = new LazyRecord\ConfigLoader;
        $loader->load( 'tests/lazy.php');
        $loader->init();

        $conM = LazyRecord\ConnectionManager::getInstance();
        $conn = $conM->getDefault();
        ok( $conn );

        $conM->closeAll();
    }
}

