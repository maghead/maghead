<?php

namespace Maghead\Schema\Loader;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ComposerSchemaLoader
{
    protected $config;

    public function __construct($composerConfig)
    {
        if (is_string($composerConfig)) {
            if (!file_exists($composerConfig)) {
                throw new \InvalidArgumentException("ComposerSchemaLoader::__construct expects the first argument to be a composer.json path or the json config array.");
            }
            $this->config = json_decode(file_get_contents($composerConfig), true);
        } else {
            $this->config = $composerConfig;
        }
    }

    protected function scanAutoload($a)
    {
        $fsLoader = new FileSchemaLoader;

        // Setup default match by
        $fsLoader->matchBy(FileSchemaLoader::MATCH_CLASSDECL);
        if (isset($a['psr-4'])) {
            foreach ($a['psr-4'] as $prefix => $ps) {
                if ($ps === "") {
                    $fsLoader->addPath(getcwd());
                } else {
                    $fsLoader->addPath($ps);
                }
            }
        }
        if (isset($a['psr-0'])) {
            foreach ($a['psr-0'] as $prefix => $ps) {
                if ($ps === "") {
                    $fsLoader->addPath(getcwd());
                } else {
                    $fsLoader->addPath($ps);
                }
            }
        }
        if (isset($a['classmap'])) {
            foreach ($a['classmap'] as $prefix => $ps) {
                if ($ps === "") {
                    $fsLoader->addPath(getcwd());
                } else {
                    $fsLoader->addPath($ps);
                }
            }
        }
        if (isset($a['files'])) {
            foreach ($a['files'] as $f) {
                $fsLoader->addPath($f);
            }
        }
        return $fsLoader->load();
    }

    public function load()
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
        return $allFiles;
    }

    public static function from($composerJson)
    {
        return new self(json_decode(file_get_contents($composerJson), true));
    }
}
