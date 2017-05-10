<?php

namespace Maghead\Runtime\Config;

use Exception;
use Symfony\Component\Yaml\Yaml;

class FileConfigWriter
{
    const ANCHOR_FILENAME = '.maghead-cli.yml';

    public static $inlineLevel = 5;

    public static $indentSpaces = 2;

    public static $currentConfig;

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

        $yaml = Yaml::dump($config->getArrayCopy(), self::$inlineLevel, self::$indentSpaces);
        if (false === file_put_contents($targetFile, "---\n".$yaml)) {
            throw new Exception("YAML config update failed: $targetFile");
        }

        return true;
    }
}
