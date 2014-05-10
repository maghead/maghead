<?php

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function createSchemaGenerator() {
        $g = new \LazyRecord\Schema\SchemaGenerator;
        $g->forceUpdate = true;
        return $g;
    }

    public function setUp()
    {
        $loader = \LazyRecord\ConfigLoader::getInstance();
        ok($loader);
        $loader->loadFromSymbol(true); // force loading
        $loader->initForBuild();
    }

    public function schemaProvider() {
        $schemas = array();
        $schemas[] = [ new \tests\UserSchema ];
        $schemas[] = [ new \tests\AddressSchema ];
        $schemas[] = [ new \tests\BookSchema ];
        $schemas[] = [ new \tests\IDNumberSchema ];
        $schemas[] = [ new \tests\NameSchema ];
        return $schemas;
    }

    /**
     * @dataProvider schemaProvider
     */
    public function test($schema)
    {
        ok($schema);

        $g = $this->createSchemaGenerator();
        ok($g);

        if ( $classMap = $g->generateCollectionClass($schema) ) {
            foreach( $classMap as $class => $file ) {
                ok($class);
                ok($file);
                path_ok($file);
                system("php -l $file");
            }
        }

        if ( $classMap = $g->generateBaseCollectionClass($schema) ) {
            foreach( $classMap as $class => $file ) {
                ok($class);
                ok($file);
                path_ok($file);
                system("php -l $file");
            }
        }

        if ( $classMap = $g->generateSchemaProxyClass($schema) ) {
            foreach( $classMap as $class => $file ) {
                ok($class);
                ok($file);
                path_ok($file);
                system("php -l $file");
            }
        }

        if ( $classMap = $g->generate(array($schema)) ) {
            ok($classMap);
            foreach( $classMap as $class => $file ) {
                ok($class);
                ok($file);
                path_ok($file,$file);
                require_once $file;
            }
        }

        $pk = $schema->findPrimaryKey();
        ok($pk, "Find primary key from " . get_class($schema) );

        $model = $schema->newModel();
        ok($model);

        $collection = $schema->newCollection();
        ok($collection);
    }
}

