<?php

namespace Maghead\Schema;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Maghead\Schema\Loader\FileSchemaLoader;

/**
 * Find schema classes from files (or from current runtime).
 *
 * 1. Find SchemaDeclare-based schema class files.
 * 2. Find model-based schema, pass dynamic schema class
 */
class SchemaFinder
{
    protected $paths = array();

    public function __construct(array $paths = array())
    {
        $this->paths = $paths;
    }

    public function in($path)
    {
        $this->paths[] = $path;
    }

    public function setPaths(array $paths)
    {
        $this->paths = $paths;
    }

    public function findByPaths(array $paths)
    {
        $loader = new FileSchemaLoader($paths);
        return $loader->load();
    }

    public function find()
    {
        if (empty($this->paths)) {
            return;
        }

        return $this->findByPaths($this->paths);
    }
}
