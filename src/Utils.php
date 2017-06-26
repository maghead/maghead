<?php

namespace Maghead;

use ReflectionObject;
use ReflectionClass;

class Utils
{
    public static function searchOption(array $values, $needle)
    {
        foreach ($values as $item) {
            // wrapped valid value
            if (is_array($item)) {
                if (isset($item['value']) && $item['value'] === $needle) {
                    return true;
                }

                //  this is for backward compatibility
                //
                //  Validate for Options
                //      "Label" => "Value",
                //      "Group" => array("Label" => "Value")
                // 
                if (isset($item['Group']) && true === self::searchOption($item['Group'], $needle)) {
                    return true;
                }

                // for grouped values
                if (isset($item['items']) && true === self::searchOption($item['items'], $needle)) {
                    return true;
                }
            }


            // for indexed array, scalar item
            if ($item === $needle) {
                return true;
            }
        }

        return false;
    }


    /**
     * try to resolve the class name if the class doesn't exist or can't be found via
     * the registered spl class loader.
     *
     * @param array $defaultNsRoots The default namespace list for lookup the class.
     * @param any $refObject The class name of the reference object will be used for lookup the class.
     * @param array $refSubDirs The subdirectories to lookup on the namespace of the reference object.
     *
     * @return string The resolved class name, if it's not changed, the original
     * class name will be returned.
     */
    public static function resolveClass($class, array $defaultNsRoots, $refObject = null, array $refSubNss = [])
    {
        // Always replace :: with '\\'
        $class = str_replace('::', '\\', $class);

        if (class_exists($class, true)) {
            return $class;
        }

        $nsRoots = $defaultNsRoots;
        if ($refObject) {
            $refl = new ReflectionObject($refObject);
            $namespaceName = $refl->getNamespaceName();
            foreach ($refSubNss as $subNs) {
                array_unshift($nsRoots,  "$namespaceName\\$subNs");
            }
            array_unshift($nsRoots, $namespaceName);
        }

        foreach ($nsRoots as $nsRoot) {
            $c = "{$nsRoot}\\{$class}";
            if (class_exists($c, true)) {
                return $c;
            }
        }

        if (!class_exists($class)) {
            return false;
        }

        return $class;
    }


    public static function mkpath($path, $mode = 0755, $logger = null)
    {
        if (is_array($path)) {
            foreach ($path as $p) {
                self::mkpath($p, $mode);
            }
        } else {
            if (!file_exists($path)) {
                if ($logger) {
                    $logger->debug("Creating $path");
                }
                mkdir($path, $mode, true);
            }
        }
    }


    public static function evaluate($data, $params = array())
    {
        if ($data && is_callable($data)) {
            return call_user_func_array($data, $params);
        }

        return $data;
    }

    public static function findBin($bin)
    {
        if ($pathstr = getenv('PATH')) {
            $paths = explode(PATH_SEPARATOR, $pathstr);
            if ($binPath = self::lookupBin($bin, $paths)) {
                return $binPath;
            }
        }
        if ($binPath = self::lookupBin($bin, ['/opt/local/bin', '/usr/local/bin', '/usr/bin', '/bin'])) {
            return $binPath;
        }
        return false;
    }

    public static function lookupBin($bin, array $paths)
    {
        foreach ($paths as $path) {
            $p = $path . DIRECTORY_SEPARATOR . $bin;
            if (file_exists($p)) {
                return $p;
            }
        }
        return false;
    }

    public static function symlink($sourcePath, $targetPath)
    {
        if (PHP_OS === 'WINNT') {
            return link($sourcePath, $targetPath);
        } else {
            return symlink($sourcePath, $targetPath);
        }
    }

    public static function filterClassesFromArgs(array $args)
    {
        return array_values(array_filter($args, function($a) {
            return class_exists($a, true);
        }));
    }

    public static function filterPathsFromArgs(array $args)
    {
        return array_filter($args, 'file_exists');
    }
}
