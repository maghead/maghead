<?php
namespace LazyRecord;
use ReflectionFunction;
use SplFileObject;

class ClosureSerializer
{

    /**
     * serialize closure
     *
     * @param Closure 
     */
    static function serialize($closure)
    {
        $ref = new ReflectionFunction($closure);
        $file = new SplFileObject($ref->getFileName());
        $file->seek($ref->getStartLine()-1);
        $code = '';
        while ($file->key() < $ref->getEndLine())
        {
            $code .= $file->current();
            $file->next();
        }
        $start = strpos($code, 'function');
        $end = strrpos($code, '}') + 1;
        return substr($code, $start, $end - $start);
    }
}

