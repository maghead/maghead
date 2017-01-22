<?php

namespace Maghead\Schema;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;
use Maghead\ServiceContainer;
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

    protected $logger;

    public function __construct(array $paths = array(), Logger $logger = null)
    {
        $this->paths = $paths;
        if (!$logger) {
            $c = ServiceContainer::getInstance();
            $logger = $c['logger'];
        }
        $this->logger = $logger;
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
        $this->logger->debug('Finding schemas in ('.implode(', ', $paths).')');

        $files = array();
        foreach ($paths as $path) {
            $this->logger->debug('Finding schemas in '.$path);
            if (is_file($path)) {
                $this->logger->debug('Loading schema: '.$path);
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
                        $this->logger->debug('Loading schema: '.$fi->getPathname());
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
