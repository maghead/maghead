<?php

namespace Maghead\Schema\Finder;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ComposerSchemaFinder
{
    protected $config;

    protected $rootDir;

    public function __construct($composerConfig = 'composer.json')
    {
        if (is_string($composerConfig)) {
            if (!file_exists($composerConfig)) {
                throw new \InvalidArgumentException("ComposerSchemaFinder::__construct expects the first argument to be a composer.json path or the json config array.");
            }
            $this->config = json_decode(file_get_contents($composerConfig), true);
            $this->rootDir = dirname(realpath($composerConfig));
        } else {
            $this->config = $composerConfig;
        }
    }

    protected function requireComposerFile($rootDir, $file)
    {
        $path = $rootDir . DIRECTORY_SEPARATOR . 'vendor' . DIRECTORY_SEPARATOR . 'composer' . DIRECTORY_SEPARATOR . $file;
        if (!file_exists($path)) {
            return false;
        }

        return require $path;
    }

    protected function scanVendor($rootDir)
    {
        $fsLoader = new FileSchemaFinder;
        if ($namespaces = $this->requireComposerFile($rootDir, 'autoload_psr4.php')) {
            foreach ($namespaces as $prefix => $dir) {
                $fsLoader->addPath($dir);
            }
        }
        if ($classMap = $this->requireComposerFile($rootDir, 'autoload_classmap.php')) {
            foreach ($classMap as $className => $classPath) {
                if (preg_match('/Schema$/', $className) && $fsLoader->scan($classPath)) {
                    $fsLoader->requireAndCollect($classPath);
                }
            }
        }
        return $fsLoader->find();
    }


    protected function scanAutoload($a)
    {
        $fsLoader = new FileSchemaFinder;

        // Setup default match by
        $fsLoader->matchBy(FileSchemaFinder::MATCH_CLASSDECL);

        $autoloadDir = $this->rootDir ?: getcwd();

        if (isset($a['psr-4'])) {
            foreach ($a['psr-4'] as $prefix => $ps) {
                $fsLoader->addPath($ps === "" ? $autoloadDir : $ps, FileSchemaFinder::MATCH_CLASSDECL);
            }
        }
        if (isset($a['psr-0'])) {
            foreach ($a['psr-0'] as $prefix => $ps) {
                $fsLoader->addPath($ps === "" ? $autoloadDir : $ps, FileSchemaFinder::MATCH_CLASSDECL);
            }
        }
        if (isset($a['classmap'])) {
            foreach ($a['classmap'] as $prefix => $ps) {
                $fsLoader->addPath($ps === "" ? $autoloadDir : $ps, FileSchemaFinder::MATCH_CLASSDECL);
            }
        }
        if (isset($a['files'])) {
            foreach ($a['files'] as $file) {
                $fsLoader->requireAndCollect($file);
            }
        }

        return $fsLoader->find();
    }

    public function find()
    {
        $allFiles = [];
        if (isset($this->config['autoload'])) {
            $files = $this->scanAutoload($this->config['autoload']);
            $allFiles = array_merge($allFiles, $files);
        }
        if (isset($this->config['autoload-dev'])) {
            $files = $this->scanAutoload($this->config['autoload-dev']);
            $allFiles = array_merge($allFiles, $files);
        }

        // If rootDir is defined, then we can scan the vendor files
        if ($this->rootDir) {
            $files = $this->scanVendor($this->rootDir);
            $allFiles = array_merge($allFiles, $files);
        }
        return $allFiles;
    }

    public static function from($composerJson)
    {
        return new self($composerJson);
    }
}
