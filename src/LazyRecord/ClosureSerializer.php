<?php
namespace LazyRecord;
use ReflectionFunction;
use SplFileObject;

class ClosureSerializer
{
    static function serialize($closure)
    {
        $ref = new ReflectionFunction($closure);
        /*
        var_dump(
            $ref->getName(), 
            $ref->getNumberOfParameters(), 
            $ref->getNumberOfRequiredParameters()
        );
         */

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

