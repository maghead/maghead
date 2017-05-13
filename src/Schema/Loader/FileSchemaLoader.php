<?php

namespace Maghead\Schema\Loader;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

class FileSchemaLoader
{
    const FILE_SUFFIX = 'Schema.php';

    protected $paths;

    protected $directoryIteratorFlags = RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS;

    public function __construct(array $paths)
    {
        $this->paths = $paths;
    }

    public function load()
    {
        $suffixLen = strlen(self::FILE_SUFFIX);

        $files = [];
        foreach ($this->paths as $path) {
            if (is_file($path)) {
                require_once $path;
                $files[] = $path;
            } elseif (is_dir($path)) {
                $rii = new RecursiveIteratorIterator(
                    new RecursiveDirectoryIterator($path, $this->directoryIteratorFlags),
                    RecursiveIteratorIterator::SELF_FIRST
                );
                foreach ($rii as $fi) {
                    $filename = $fi->getFilename();
                    if (substr($filename, - $suffixLen) == self::FILE_SUFFIX) {
                        require_once $fi->getPathname();

                        // TODO: Inspect the file and return the class names
                        $files[] = $path;
                    }
                }
            }
        }
        return $files;
    }
}
