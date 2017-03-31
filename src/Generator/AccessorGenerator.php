<?php

namespace Maghead\Generator;

use Maghead\Schema\DeclareSchema;
use Maghead\Schema\DeclareColumn;
use Maghead\Schema\Relationship\Relationship;
use Maghead\Manager\DataSourceManager;

use Doctrine\Common\Inflector\Inflector;


use SQLBuilder\Universal\Query\SelectQuery;
use SQLBuilder\Universal\Query\DeleteQuery;
use SQLBuilder\Bind;
use SQLBuilder\ParamMarker;
use SQLBuilder\ArgumentArray;

use ClassTemplate\ClassFile;
use CodeGen\Statement\RequireStatement;
use CodeGen\Statement\RequireOnceStatement;
use CodeGen\Expr\ConcatExpr;
use CodeGen\Raw;

class AccessorGenerator
{
    public static function generateSetterAccessor(ClassFile $cTemplate, DeclareColumn $column, string $accessorName, string $propertyName)
    {
        $cTemplate->addMethod('public', $accessorName, ['$val'], function () use ($column, $propertyName) {
            $columnName = $column->name;
            if ($column->get('deflator')) {
                return [
                    "if (\$c = \$this->getSchema()->getColumn(\"$columnName\")) {",
                    "     return \$c->deflate(\$this->{$columnName}, \$this);",
                    "}",
                    "return \$this->{$columnName};",
                ];
            }

            switch ($column->isa) {
                case "json":
                    return "\$this->{$columnName} = json_encode(\$val);";
                case "DateTime":
                    return [
                        "if (\$val instanceof DateTime) {",
                        // FIXME: the deflator requires QueryDriver to deflate
                        // the object because the format may vary based on
                        // different driver type.
                        // "   if (\$driver instanceof PDOMySQLDriver) {",
                        // "        return \$val->format('Y-m-d H:i:s');",
                        // "    }",
                        "",
                        "    return \$val->format(DateTime::ATOM);",
                        "}",
                    ];
                    break;
            }


            return "\$this->{$columnName} = \$val;";
        });
    }

    public static function generateGetterAccessor(ClassFile $cTemplate, DeclareColumn $column, string $accessorName, string $propertyName)
    {
        $cTemplate->addMethod('public', $accessorName, [], function () use ($column, $propertyName) {
            $columnName = $column->name;
            if ($column->get('inflator')) {
                return [
                    "if (\$c = \$this->getSchema()->getColumn(\"$columnName\")) {",
                    "     return \$c->inflate(\$this->{$columnName}, \$this);",
                    "}",
                    "return \$this->{$columnName};",
                ];
            }
            if ($column->isa === "int") {
                return ["return intval(\$this->{$columnName});"];
            } elseif ($column->isa === "str") {
                return ["return \$this->{$columnName};"];
            } elseif ($column->isa === "bool") {
                return [
                    "\$value = \$this->{$columnName};",
                    "if (\$value === '' || \$value === null) {",
                    "   return null;",
                    "}",
                    "return boolval(\$value);",
                ];
            } elseif ($column->isa === "float") {
                return ["return floatval(\$this->{$columnName});"];
            } elseif ($column->isa === "json") {
                return ["return json_decode(\$this->{$columnName});"];
            }
            return ["return Inflator::inflate(\$this->{$columnName}, '{$column->isa}');"];
        });
    }
}
