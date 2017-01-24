<?php
namespace Maghead\Schema;

class PDOStatementCodeGen
{
    public static function generateFetch($propertyName, $constName, $class, $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "    \$this->{$propertyName} = \$this->read->prepare(self::$constName);",
            "    \$this->{$propertyName}->setFetchMode(PDO::FETCH_CLASS, '\\{$class}');",
            "}",
            "return static::_stmFetch(\$this->{$propertyName}, $args);",
        ];
    }

    public static function generateExecute($propertyName, $constName, $args)
    {
        return [
            "if (!\$this->{$propertyName}) {",
            "   \$this->{$propertyName} = \$this->write->prepare(self::$constName);",
            "}",
            "return \$this->{$propertyName}->execute($args);",
        ];
    }
}
