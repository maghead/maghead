<?php

namespace Maghead\Schema\Comparator;

use CLIFramework\Formatter;

class ConsolePrinter
{
    public $diff = array();

    public $beforeName = 'old';

    public $afterName = 'new';

    public function __construct(array $diff)
    {
        $this->diff = $diff;
    }

    public function output()
    {
        if (empty($this->diff)) {
            return;
        }
        $formatter = new Formatter();

        echo $formatter->format('--- '.$this->beforeName, 'strong_white') , "\n";
        echo $formatter->format('+++ '.$this->afterName, 'strong_white') , "\n";
        echo "@@ columns @@\n";

        foreach ($this->diff as $d) {
            // for each diff items, show attribute diff
            switch ($d->flag) {
                case 'M':
                    echo $formatter->format('M '.$d->name, 'yellow') , "\n";
                    foreach ($d->details as $attrDiff) {
                        echo "\t".$formatter->format($attrDiff->getBeforeDescription(), 'red');
                        echo "\t".$formatter->format($attrDiff->getAfterDescription(), 'green');
                    }
                    break;
                case 'A':
                    $line = $d->toColumnAttrsString();
                    echo $formatter->format($line."\n", 'green');
                    break;
                case 'D':
                    $line = $d->toColumnAttrsString();
                    echo $formatter->format($line."\n", 'red');
                    break;
            }
        }
    }
}
