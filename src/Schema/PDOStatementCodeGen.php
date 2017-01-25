<?php
namespace Maghead\Schema;

class PDOStatementCodeGen
{

    public static function generateFetchAll(string $propertyName, string $constName, string $class, string $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "    \$this->{$propertyName} = \$this->read->prepare(self::$constName);",
            "    \$this->{$propertyName}->setFetchMode(PDO::FETCH_CLASS, '\\{$class}');",
            "}",
            "return static::_stmFetchAll(\$this->{$propertyName}, $args);",
        ];
    }

    public static function generateFetchOne(string $propertyName, string $constName, string $class, string $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "    \$this->{$propertyName} = \$this->read->prepare(self::$constName);",
            "    \$this->{$propertyName}->setFetchMode(PDO::FETCH_CLASS, '\\{$class}');",
            "}",
            "return static::_stmFetchOne(\$this->{$propertyName}, $args);",
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
