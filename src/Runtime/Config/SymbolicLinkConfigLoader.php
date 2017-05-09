<?php

namespace Maghead\Runtime\Config;

class SymbolicLinkConfigLoader extends FileConfigLoader
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
        return parent::load(realpath($file), $force);
    }
}
