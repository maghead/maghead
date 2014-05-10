<?php

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function createSchemaGenerator() {
        $g = new \LazyRecord\Schema\SchemaGenerator;
        $g->forceUpdate = true;
        return $g;
    }

    public function schemaProvider() {
        $schemas = array();
        $schemas[] = [ new \tests\UserSchema ];
        $schemas[] = [ new \tests\AddressSchema ];
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

            $schemaProxy = new \tests\UserSchemaProxy;
            ok($schemaProxy);
            $baseClass = new \tests\UserBase;
            ok($baseClass);
            $class = \tests\UserBase::collection_class;
            $o = new $class;
            ok($o);
        }
    }
}

