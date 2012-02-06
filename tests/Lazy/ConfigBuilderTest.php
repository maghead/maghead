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
        $loader->load( 'tests/lazy-config.php');

        $conM = Lazy\ConnectionManager::getInstance();
        $conn = $conM->getDefault();
        ok( $conn );

        $conM->closeAll();
    }
}

