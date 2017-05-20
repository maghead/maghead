<?php

namespace Maghead\Runtime\Config;

use ConfigKit\ConfigCompiler;
use RuntimeException;

class FileConfigLoader
{
    /**
     * Load config from the YAML config file...
     *
     * @param string $file
     */
    public static function load($sourceFile, $force = false)
    {
        if (preg_match('/\.php$/', $sourceFile)) {
            $stash = require $sourceFile;
            return new Config($stash);
        }
        $compiledFile = self::compile($sourceFile, $force);
        $stash = require $compiledFile;
        return new Config($stash, $sourceFile);
    }

    /**
     * compile the source config file.
     *
     * @return path the compiled config file
     */
    public static function compile($sourceFile, $force = false)
    {
        $compiledFile = ConfigCompiler::compiled_filename($sourceFile);
        if ($force || ConfigCompiler::test($sourceFile, $compiledFile)) {
            $config = ConfigCompiler::parse($sourceFile);
            if ($config === false) {
                throw new RuntimeException("Can't parse config file '$sourceFile'.");
            }
            $config = ConfigPreprocessor::preprocess($config);
            ConfigCompiler::write($compiledFile, $config);
        }
        return $compiledFile;
    }
}
