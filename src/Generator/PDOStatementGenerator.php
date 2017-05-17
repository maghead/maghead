<?php
namespace Maghead\Generator;

class PDOStatementGenerator
{
    public static function generateReadPrepare(string $sql)
    {
        return [
            "return \$this->read->prepare(" . var_export($sql, true) . ");",
        ];
    }

    public static function generateFetchAll(string $propertyName, string $constName, string $class, string $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "    \$this->{$propertyName} = \$this->read->prepare(self::$constName);",
            "    \$this->{$propertyName}->setFetchMode(PDO::FETCH_CLASS, \\{$class}::class, [\$this]);",
            "}",
            "\$this->{$propertyName}->execute($args);",
            // "return \$this->{$propertyName}->fetchAll(PDO::FETCH_CLASS);",
            "return \$this->{$propertyName}->fetchAll();",
        ];
    }

    public static function generateFetchOne(string $propertyName, string $constName, string $class, string $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "    \$this->{$propertyName} = \$this->read->prepare(self::$constName);",
            "    \$this->{$propertyName}->setFetchMode(PDO::FETCH_CLASS, \\{$class}::class, [\$this]);",
            "}",
            "\$this->{$propertyName}->execute($args);",
            "\$obj = \$this->{$propertyName}->fetch();",
            "\$this->{$propertyName}->closeCursor();",
            "return \$obj;",
        ];
    }

    public static function generateExecute(string $propertyName, string $constName, string $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "   \$this->{$propertyName} = \$this->write->prepare(self::$constName);",
            "}",
            "return \$this->{$propertyName}->execute($args);",
        ];
    }
}
