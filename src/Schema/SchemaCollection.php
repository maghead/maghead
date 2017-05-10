<?php

namespace Maghead\Schema;

use InvalidArgumentException;
use ReflectionClass;
use ArrayObject;

class SchemaCollection extends ArrayObject
{
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
            return is_subclass_of($schema, 'Maghead\\Schema\\DeclareSchema', true);
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

    public function buildable()
    {
        return $this->filter(function($schema) {
            if (
              !is_subclass_of($schema, 'Maghead\\Schema\\DeclareSchema', true)
              || is_a($schema, 'Maghead\\Schema\\DynamicSchemaDeclare', true)
              || is_a($schema, 'Maghead\\Schema\\MixinDeclareSchema', true)
              || is_subclass_of($schema, 'Maghead\\Schema\\MixinDeclareSchema', true)
            ) {
                return false;
            }

            // Skip abstract class files...
            $rf = new ReflectionClass($schema);
            return !$rf->isAbstract();
        });
    }

    public static function evaluateArray(array $classes)
    {
        $schemas = array_map(function ($a) {
            return is_string($a) ? new $a() : $a;
        }, $classes);

        return new self($schemas);
    }
}
