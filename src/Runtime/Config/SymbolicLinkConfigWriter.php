<?php

namespace Maghead\Runtime\Config;

use Exception;

class SymbolicLinkConfigWriter extends FileConfigWriter
{
    const ANCHOR_FILENAME = '.maghead-cli.yml';

    /**
     * This is used when running command line application
     */
    public static function write(Config $config, $targetFile = null)
    {
        if (!$targetFile) {
            if (!file_exists(self::ANCHOR_FILENAME)) {
                throw new Exception('symbol link '.self::ANCHOR_FILENAME.' does not exist.');
            }
            $targetFile = readlink(self::ANCHOR_FILENAME);
        }
        if (!$targetFile && !file_exists($targetFile)) {
            throw new Exception('Missing target config file. incorrect symbol link.');
        }

        return parent::write($config, $targetFile);
    }
}
