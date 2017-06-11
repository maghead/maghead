<?php

namespace Maghead\Generator;

use Maghead\Schema\DeclareSchema;
use CodeGen\UserClass;

class GetSchemaMethodGenerator
{
    public static function generate(UserClass $class, DeclareSchema $schema)
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
