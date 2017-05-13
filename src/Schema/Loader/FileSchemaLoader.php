<?php

namespace Maghead\Schema\Loader;

use RecursiveDirectoryIterator;
use RecursiveIteratorIterator;

use ArrayObject;

class MatchSet extends ArrayObject {

    public $matchBy;

    function __construct(array $files, $matchBy = null)
    {
        parent::__construct($files);
        $this->matchBy = $matchBy;
    }
}

class FileSchemaLoader
{
    const FILE_SUFFIX = 'Schema.php';

    const MATCH_FILENAME = 1;

    const MATCH_CLASSDECL = 2;

    const CLASSDECL_PATTERN = '/extends\s+((?:Maghead\\\\Schema\\\\)?(?:Declare|Mixin|\w+)Schema)/sm';

    protected $paths;

    protected $fileSuffixLen;

    protected $matchBy = self::MATCH_FILENAME;

    protected $directoryIteratorFlags = RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS;

    protected $collectedFiles = [];

    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
        $this->fileSuffixLen = strlen(self::FILE_SUFFIX);
    }

    public function addPath($p, $matchBy = null)
    {
        $this->paths[] = new MatchSet((array) $p, $matchBy);
    }

    public function matchBy($m)
    {
        $this->matchBy = $m;
    }

    protected function scanPaths(array $paths, $matchBy)
    {
        foreach ($paths as $a) {
            if ($a instanceof MatchSet) {
                $this->scanPaths($a->getArrayCopy(), $a->matchBy ?: $matchBy);
            } else {
                $path = $a;
                if (is_file($path)) {
                    require_once $path;
                    $this->collectedFiles[] = $path;
                } else if (is_dir($path)) {
                    $rii = new RecursiveIteratorIterator(
                        new RecursiveDirectoryIterator($path, $this->directoryIteratorFlags),
                        RecursiveIteratorIterator::SELF_FIRST
                    );
                    foreach ($rii as $fi) {
                        $filename = $fi->getFilename();
                        switch ($matchBy) {
                            case self::MATCH_FILENAME:
                                if (substr($filename, - $this->fileSuffixLen) == self::FILE_SUFFIX) {
                                    require_once $fi->getPathname();
                                    $this->collectedFiles[] = $path;
                                }
                                break;
                            case self::MATCH_CLASSDECL:
                                $content = file_get_contents($fi->getPathname());
                                if (preg_match(self::CLASSDECL_PATTERN, $content, $matches)) {
                                    require_once $fi->getPathname();
                                    $this->collectedFiles[] = $path;
                                }
                                break;
                        }
                    }
                }
            }
        }
    }

    public function load()
    {
        $this->scanPaths($this->paths, $this->matchBy);
        return $this->collectedFiles;
    }
}
