<?php
namespace Maghead\Schema;

class AnnotatedBlock
{
    public $id;

    public $lines;

    public $range;

    public function __construct($id, array $lines = [], array $range = null) {
        $this->id = $id;
        $this->lines = $lines;
        $this->range = $range;
    }

    static public function apply(array $elements, array $settings)
    {
        $body = [];
        foreach ($elements as $el) {
            if ($el instanceof AnnotatedBlock) {
                $blockId = $el->id;
                if (isset($settings[$blockId]) && isset($el->lines)) {
                    if ($settings[$blockId]) {
                        $body[] = $el->lines;
                    }
                }
            } else {
                $body[] = $el;
            }
        }
        return $body;
    }
}
