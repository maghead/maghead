<?php
use LazyRecord\ConfigLoader;
use CLIFramework\Logger;

class SchemaGeneratorTest extends PHPUnit_Framework_TestCase
{
    public function createSchemaGenerator() {
        $g = new \LazyRecord\Schema\SchemaGenerator(ConfigLoader::getInstance(), Logger::getInstance());
        $g->forceUpdate = true;
        return $g;
    }

    public function schemaProvider() {
        $loader = \LazyRecord\ConfigLoader::getInstance();
        ok($loader);
        $loader->loadFromArray(array( 
            'bootstrap' =>
            array ( 0 => 'tests/bootstrap.php',),
            'schema' => array (
                'auto_id' => 1,
                'paths' => array ( 0 => 'tests/TestApp',),
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
        )); // force loading

        $g = $this->createSchemaGenerator();

        $schemas = array();
        $schemas[] = [ $g, new \TestApp\Model\UserSchema ];
        $schemas[] = [ $g, new \AuthorBooks\Model\AddressSchema ];
        $schemas[] = [ $g, new \AuthorBooks\Model\BookSchema ];
        $schemas[] = [ $g, new \TestApp\Model\IDNumberSchema ];
        $schemas[] = [ $g, new \TestApp\Model\NameSchema ];
        return $schemas;
    }


    /**
     * @dataProvider schemaProvider
     */
    public function testBaseCollectionClassGeneration($g, $schema)
    {
        if ( $result = $g->generateBaseCollectionClass($schema) ) {
            list($class, $file) = $result;
            ok($class);
            ok($file);
            path_ok($file);
            $this->syntaxTest($file);
        }
    }

    /**
     * @dataProvider schemaProvider
     */
    public function testCollectionClassGeneration($g, $schema)
    {
        if ( $result = $g->generateCollectionClass($schema) ) {
            list($class, $file) = $result;
            ok($class);
            ok($file);
            path_ok($file);
            $this->syntaxTest($file);
        }
    }

    public function syntaxTest($file) {
        $this->expectOutputRegex('/^No syntax errors detected/' );
        system("php -l $file");
    }


    /**
     * @dataProvider schemaProvider
     */
    public function testGenerateMethod($g, $schema) 
    {
        if ( $classMap = $g->generate(array($schema)) ) {
            $this->expectOutputRegex('/Checking /' );
            ok($classMap);
            foreach( $classMap as $class => $file ) {
                ok($class);
                ok($file);
                path_ok($file,$file);
                // $this->syntaxTest($file);
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

    /**
     * @dataProvider schemaProvider
     */
    public function testSchemaProxyGeneration($g, $schema)
    {
        if ( $result = $g->generateSchemaProxyClass($schema) ) {
            list($class, $file) = $result;
            ok($class);
            ok($file);
            path_ok($file);
            $this->syntaxTest($file);
        }
    }
}

