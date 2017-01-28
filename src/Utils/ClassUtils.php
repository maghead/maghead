<?php

namespace Maghead\Utils;

use Exception;
use ReflectionClass;
use Doctrine\Common\Inflector\Inflector;

class ClassUtils
{
    public static function filterExistingClasses(array $classes)
    {
        return array_filter($classes, function ($class) {
            return class_exists($class, true);
        });
    }
}
