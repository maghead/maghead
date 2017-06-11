<?php

namespace Maghead\Generator;

use Maghead\Schema\DeclareSchema;
use CodeGen\UserClass;
use CodeGen\ClassFile;

class GetSchemaMethodGenerator
{
    public static function generate(ClassFile $class, DeclareSchema $schema)
    {
        $schemaProxyClass = $schema->getSchemaProxyClass();
        $class->addStaticMethod('public', 'getSchema', [], function () use ($schemaProxyClass) {
            return [
                "static \$schema;",
                "if (\$schema) {",
                "   return \$schema;",
                "}",
                "return \$schema = new \\{$schemaProxyClass};",
            ];
        });
    }
}
