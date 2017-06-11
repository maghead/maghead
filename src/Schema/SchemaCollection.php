<?php

namespace Maghead\Schema;

use InvalidArgumentException;
use ReflectionClass;
use ArrayObject;

class SchemaCollection extends ArrayObject
{
    public function __construct($a)
    {
        if ($a instanceof SchemaCollection) {
            parent::__construct($a->getArrayCopy());
        } else {
            parent::__construct($a);
        }
    }

    public function filter(callable $cb)
    {
        $a = [];
        foreach ($this as $s) {
            if ($cb($s)) {
                $a[] = $s;
            }
        }
        return new self($a);
    }

    public function map(callable $cb)
    {
        $a = [];
        foreach ($this as $s) {
            $a[] = $cb($s);
        }
        return new self($a);
    }

    public function evaluate()
    {
        return $this->map(function ($a) {
            return is_string($a) ? new $a : $a;
        });
    }


    public function expandDependency()
    {
        $expands = [];
        foreach ($this as $schema) {
            $expands = array_merge($expands, $this->expandSchemaDependency($schema));
        }
        $expands = array_unique($expands);
        return new self($expands);
    }

    protected function expandSchemaDependency(DeclareSchema $schema)
    {
        $expands = array();
        $refs = $schema->getReferenceSchemas();
        foreach ($refs as $refClass => $v) {
            // $refSchema = new $refClass;
            // $expand = array_merge($expand, $this->expandSchemaDependency($refSchema), array($refClass));
            $expands[] = $refClass;
        }
        $expands[] = get_class($schema);

        return array_unique($expands);
    }

    /**
     * @return Maghead\Schema\DeclareSchema[]
     */
    public function getDeclareSchemas()
    {
        return $this->filter(function ($schema) {
            return is_subclass_of($schema, DeclareSchema::class, true);
        });
    }

    public function getSchemas()
    {
        return $this;
    }

    public function classes()
    {
        return $this->map(function ($a) {
            return get_class($a);
        });
    }

    public function notForTest()
    {
        return $this->filter(function($schema) {
            $cls = is_string($schema) ? $schema : get_class($schema);
            return !preg_match('/^Test\w+$/i', $cls);
        });
    }

    public function exists()
    {
        return $this->filter(function($s) {
            if (is_object($s)) {
                return $s;
            }
            return class_exists($s, true);
        });
    }

    public function unique()
    {
        $map = [];
        foreach ($this as $s) {
            $k = is_string($s) ? $s : get_class($s);
            $map[$k] = $s;
        }
        return new self(array_values($map));
    }

    public function buildable()
    {
        return $this->filter(function($schema) {
            if (
              !is_subclass_of($schema, DeclareSchema::class, true)
              || is_a($schema, DynamicSchemaDeclare::class, true)
              || is_a($schema, MixinDeclareSchema::class, true)
              || is_subclass_of($schema, MixinDeclareSchema::class, true)
            ) {
                return false;
            }

            // Skip abstract class files...
            $rf = new ReflectionClass($schema);
            return !$rf->isAbstract();
        });
    }


    /**
     * return the table schema map
     */
    public function tables()
    {
        $tableMap = [];

        // map table names to declare schema objects
        foreach ($this as $a) {
            $s = is_string($a) ? SchemaLoader::load($a) : $a;
            $tableMap[$s->getTable()] = $s;
        }

        return new self($tableMap);
    }

    /**
     * Return declared schema class collection
     */
    public static function declared()
    {
        // Trigger spl to load metadata schema
        class_exists('Maghead\\Model\\MetadataSchema', true);

        $classes = get_declared_classes();

        return (new self($classes))->buildable();
    }

    public static function create(array $args)
    {
        return new self($args);
    }

    public static function evaluateArray(array $classes)
    {
        $schemas = array_map(function ($a) {
            return is_string($a) ? SchemaLoader::load($a) : $a;
        }, $classes);

        return new self($schemas);
    }
}
