<?php
namespace Maghead\Migration;

use Maghead\Manager\DataSourceManager;
use Maghead\Runtime\Connection;
use GetOptionKit\OptionResult;
use CLIFramework\Logger;
use Magsql\Driver\BaseDriver;
use Exception;
use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use Maghead\Migration\Migration;

class MigrationLoader
{
    /**
     * find and require migration scripts and return the file path of the
     * migration scripts in a specific directory.
     *
     * @return path[]
     */
    public static function findIn($directory)
    {
        if (!file_exists($directory)) {
            return array();
        }
        $loaded = array();
        $iterator = new RecursiveIteratorIterator(
            new RecursiveDirectoryIterator($directory), RecursiveIteratorIterator::CHILD_FIRST
        );
        foreach ($iterator as $path) {
            if ($path->isFile() && $path->getExtension() === 'php') {
                $code = file_get_contents($path);

                // If the code mentioned "Migration" as keyword.
                if (preg_match('#Maghead\\Migration#', $code)) {
                    require_once $path;
                    $loaded[] = $path;
                }
            }
        }

        return $loaded;
    }


    public static function sortMigrationScripts(array $classes)
    {
        // sort class with timestamp suffix
        usort($classes, function ($a, $b) {
            if (preg_match('#_(\d+)$#', $a, $regsA) && preg_match('#_(\d+)$#', $b, $regsB)) {
                list($aId, $bId) = array($regsA[1], $regsB[1]);
                if ($aId == $bId) {
                    return 0;
                }

                return $aId < $bId ? -1 : 1;
            }

            return 0;
        });
        return $classes;
    }


    /**
     * Return the declared migration scripts in ascending order by timestamp.
     *
     * @return className[]
     */
    public static function getDeclaredMigrationScripts()
    {
        $classes = array_filter(get_declared_classes(), function ($class) {
            return is_a($class, Migration::CLASS, true)
                && $class !== Migration::CLASS
                && !preg_match('/^Test/', $class)
                ;
        });
        return self::sortMigrationScripts($classes);
    }
}
