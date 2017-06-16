<?php

namespace Maghead\Schema;

use SplObjectStorage;
use ArrayObject;
use Maghead\Schema\Relationship\BelongsTo;
use CLIFramework\Logger;

class ClassInstanceMap extends ArrayObject {

    public function add($obj)
    {
        $this[get_class($obj)] = $obj;
    }

}


class SchemaDependencyResolver
{
    protected $resolved;

    protected $resolvedStorage;

    protected $traced;

    protected $logger;

    protected $classMap;

    public function __construct(Logger $logger)
    {
        $this->logger = $logger;
    }

    public function resolve(SchemaCollection $classes)
    {
        $this->resolved = new SchemaCollection([]);
        $this->resolvedStorage = new SplObjectStorage;
        $this->traced = new SplObjectStorage;

        $schemas = $classes->evaluate();

        $this->classMap = new ClassInstanceMap;
        foreach ($schemas as $schema) {
            $this->classMap->add($schema);
        }

        $this->logger->debug("start tracing ...");
        foreach ($schemas as $schema) {
            $this->traceUp($schema);
        }

        return $this->resolved;
    }

    protected function traceUp(DeclareSchema $schema)
    {
        $this->logger->debug("trace {$schema}");
        $this->logger->indent();
        $this->traced->attach($schema);

        $rels = $schema->getRelations();
        foreach ($rels as $relKey => $rel) {
            if ($rel instanceof BelongsTo) {
                $foreignSchemaClass = $rel['foreign_schema'];
                if (!isset($this->classMap[$foreignSchemaClass])) {
                    $this->classMap->add(new $foreignSchemaClass);
                }

                $fs = $this->classMap[$foreignSchemaClass];
                if (!$this->traced->contains($fs)) {
                    $this->logger->debug("found belongs to relationship {$schema}.{$relKey} => {$foreignSchemaClass}");
                    $this->traceUp($fs);
                } else {
                    $this->logger->debug("already traced {$schema}.{$relKey} => {$foreignSchemaClass}");
                }
            }
        }

        if (!$this->resolvedStorage->contains($schema)) {
            $this->logger->debug("adding {$schema}");
            $this->resolved[] = $schema;
            $this->resolvedStorage->attach($schema);
        }

        $this->logger->unIndent();
    }
}
