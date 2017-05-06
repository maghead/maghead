<?php

namespace Maghead\Schema;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use CLIFramework\Logger;

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
        $files = array();
        foreach ($paths as $path) {
            if (is_file($path)) {
                require_once $path;
                $files[] = $path;
            } elseif (is_dir($path)) {
                $rii = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path,
                        RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS
                    ),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($rii as $fi) {
                    if (substr($fi->getFilename(), -10) == 'Schema.php') {
                        require_once $fi->getPathname();
                        $files[] = $path;
                    }
                }
            }
        }

        return $files;
    }

    public function find()
    {
        if (empty($this->paths)) {
            return;
        }

        return $this->findByPaths($this->paths);
    }
}
