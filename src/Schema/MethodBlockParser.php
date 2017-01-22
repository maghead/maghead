<?php
namespace Maghead\Schema;
use ReflectionMethod;

class MethodBlockParser
{
    /**
     * parseMethod doesn't return block mapping, it returns the lines and blocks in sequence.
     */
    static public function parseElements(ReflectionMethod $method, $tag)
    {
        $methodFile = $method->getFilename();
        $startLine = $method->getStartLine();
        $endLine = $method->getEndLine();
        $lines = file($methodFile);
        $methodLines = array_slice($lines, $startLine + 1, $endLine - $startLine - 2); // exclude '{', '}'
        $blocks = [];

        $numberOfLines = count($methodLines);

        $indent = 0;
        if (preg_match('/^(\s+)/',$methodLines[0], $m)) {
            $indent = strlen($m[0]);
        }

        for ($i = 0; $i < $numberOfLines; ++$i) {
            $line = substr(rtrim($methodLines[$i]), $indent);

            if (preg_match("/@$tag (\w+)/", $line, $matches)) {
                $blockId = $matches[1];
                $block = new AnnotatedBlock($blockId);
                for ($j = $i; $j < $numberOfLines; ++$j) {
                    // $line = rtrim($methodLines[$j]);
                    $line = substr(rtrim($methodLines[$j]), $indent);
                    $block->lines[] = $line ?: '';
                    if (preg_match("/@{$tag}End/", $line)) {
                        $block->range = [$i, $j];
                        $i = $j; // find the next block
                        break;
                    }
                }
                $blocks[] = $block;
            } else {
                $blocks[] = $line ?: '';
            }
        }
        return $blocks;

    }
}
