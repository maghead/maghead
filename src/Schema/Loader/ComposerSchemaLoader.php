<?php

namespace Maghead\Schema\Loader;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class ComposerSchemaLoader
{
    protected $config;

    public function __construct(array $composerConfig)
    {
        $this->config = $composerConfig;
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
        if (isset($this->config['autoload'])) {
            $files = $this->scanAutoload($this->config['autoload']);
        }
        if (isset($this->config['autoload-dev'])) {
            $devFiles = $this->scanAutoload($this->config['autoload-dev']);
        }
        return array_merge($files, $devFiles);
    }

    public static function from($composerJson)
    {
        return new self(json_decode(file_get_contents($composerJson), true));
    }
}
