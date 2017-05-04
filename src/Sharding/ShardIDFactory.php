<?php

namespace Maghead\Sharding;

class ShardIDFactory
{
    public static function generate($prefix, $numberOfShards, $startFrom = 0, $step = 1)
    {
        $list = [];
        $i = $startFrom;
        while (--$numberOfShards) {
            $list[] = "{$prefix}{$i}";
            $i += $step;
        }
        return $list;
    }

    public static function generateByIndexes($prefix, array $indexes)
    {
        return array_map(function ($x) use ($prefix) {
            return "{$prefix}{$x}";
        }, $indexes);
    }
}
