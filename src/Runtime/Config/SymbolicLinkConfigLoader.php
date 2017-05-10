<?php

namespace Maghead\Runtime\Config;

class SymbolicLinkConfigLoader
{
    const ANCHOR_FILENAME = '.maghead-cli.yml';

    /**
     * This is used when running command line application
     */
    public static function load($force = false)
    {
        $file = self::ANCHOR_FILENAME;
        // TODO: lookup config in the fallback directories
        if (!file_exists($file)) {
            return false;
        }

        $file = realpath($file);
        return FileConfigLoader::load(realpath($file), $force);
    }
}
