<?php

namespace Maghead\Runtime\Config;

class ArrayConfigLoader
{
    /**
     * Load config from array directly.
     *
     * @param array $config
     */
    public static function load(array $configArray)
    {
        return new Config(ConfigPreprocessor::preprocess($configArray));
    }
}
