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

    const CLASSDECL_PATTERN = '/Schema\s+extends\s+((?:Maghead\\\\Schema\\\\)?(?:Declare|Mixin|Template|\w+)Schema)/sm';

    protected $paths;

    protected $fileSuffixLen;

    protected $matchBy = self::MATCH_CLASSDECL;

    protected $directoryIteratorFlags = RecursiveDirectoryIterator::SKIP_DOTS | RecursiveDirectoryIterator::FOLLOW_SYMLINKS;

    protected $collectedFiles = [];

    /**
     * Includes '/vendor/' to ignore schema defined in the vendor directory.
     */
    protected $ignorePatterns = ['Test\.php$', '/(?:\.git|\.svn|vendor)/'];

    private $compiledIgnorePattern;

    public function __construct(array $paths = [])
    {
        $this->paths = $paths;
        $this->fileSuffixLen = strlen(self::FILE_SUFFIX);
    }

    public function addIgnorePattern($pattern)
    {
        $this->ignorePatterns[] = $pattern;
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
                        $filepath = $fi->getPathname();

                        if ($this->compiledIgnorePattern && preg_match($this->compiledIgnorePattern, $filepath)) {
                            echo "ignore $filepath\n";
                            continue;
                        }


                        // skip non php files
                        if ('php' !== $fi->getExtension()) {
                            continue;
                        }


                        // skip unit test files
                        /*
                        if (preg_match('/Test\.php$/i', $filename)) {
                            continue;
                        }
                        */


                        switch ($matchBy) {
                            case self::MATCH_FILENAME:
                                if (substr($filename, - $this->fileSuffixLen) == self::FILE_SUFFIX) {
                                    require_once $fi->getPathname();
                                    $this->collectedFiles[] = $filepath;
                                }
                                break;
                            case self::MATCH_CLASSDECL:
                                $content = file_get_contents($fi->getPathname());
                                if (preg_match(self::CLASSDECL_PATTERN, $content, $matches)) {
                                    require_once $fi->getPathname();
                                    $this->collectedFiles[] = $filepath;
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
        $this->compiledIgnorePattern = join('|', array_map(function($p) {
            $p = str_replace('#', '\\#', $p);
            return "(?:$p)";
        }, $this->ignorePatterns));
        $this->compiledIgnorePattern = "!{$this->compiledIgnorePattern}!";

        $this->scanPaths($this->paths, $this->matchBy);
        return $this->collectedFiles;
    }
}
