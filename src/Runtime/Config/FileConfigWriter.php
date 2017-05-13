<?php

namespace Maghead\Runtime\Config;

use Exception;
use Symfony\Component\Yaml\Yaml;

class FileConfigWriter
{
    public static $inlineLevel = 5;

    public static $indentSpaces = 2;

    public static $currentConfig;

    public static function write(Config $config, $targetFile)
    {
        if (is_link($targetFile)) {
            $targetFile = readlink($targetFile);
        }

        $yaml = Yaml::dump($config->getArrayCopy(), self::$inlineLevel, self::$indentSpaces);
        if (false === file_put_contents($targetFile, "---\n".$yaml)) {
            throw new Exception("YAML config update failed: $targetFile");
        }

        return true;
    }
}
