<?php
use Maghead\ConfigLoader;
use Maghead\ConnectionManager;
use CLIFramework\Logger;
use Maghead\Testing\ModelTestCase;
use \Maghead\Schema\SchemaGenerator;

class SchemaGeneratorTest extends ModelTestCase
{
    public function getModels()
    {
        return [
            new \TestApp\Model\UserSchema,
            new \AuthorBooks\Model\AddressSchema,
            new \AuthorBooks\Model\BookSchema,
            new \TestApp\Model\IDNumberSchema,
            new \TestApp\Model\NameSchema
        ];
    }

    public function testSchemaGenerator()
    {
        $g = new SchemaGenerator($this->config, $this->logger);
        $g->setForceUpdate(true);
        $schemas = $this->getModels();

        foreach ($schemas as $schema) {
            if ($result = $g->generateCollectionClass($schema)) {
                list($class, $file) = $result;
                ok($class);
                ok($file);
                path_ok($file);
                $this->syntaxTest($file);
            }

            if ( $classMap = $g->generate(array($schema)) ) {
                foreach( $classMap as $class => $file ) {
                    ok($class);
                    ok($file);
                    path_ok($file,$file);
                    // $this->syntaxTest($file);
                    require_once $file;
                }
            }

            $pk = $schema->findPrimaryKey();
            $this->assertNotNull($pk, "Find primary key from " . get_class($schema) );

            $model = $schema->newModel();
            $this->assertNotNull($model);

            $collection = $schema->newCollection();
            $this->assertNotNull($collection);
        }
    }

    public function syntaxTest($file) {
        $this->expectOutputRegex('/^No syntax errors detected/' );
        system("php -l $file");
    }
}
