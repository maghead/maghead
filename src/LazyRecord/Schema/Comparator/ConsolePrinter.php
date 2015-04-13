<?php
namespace LazyRecord\Schema\Comparator;
use Closure;
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
        if ( empty($this->diff) ) {
            return;
        }
        $formatter = new Formatter;

        echo $formatter->format('--- ' . $this->beforeName,"strong_white") , "\n";
        echo $formatter->format('+++ ' . $this->afterName, "strong_white") , "\n";
        echo "@@ columns @@\n";

        foreach ($this->diff as $d) {
            // for each diff items, show attribute diff
            if ($d->flag == '=') {
                echo '=' , ' ' , $d->name , "\n";
                foreach ($d->details as $attrDiff) {
                    echo $formatter->format($attrDiff->getBeforeDescription(), 'red');
                    echo $formatter->format($attrDiff->getAfterDescription(), 'green');
                }
            } else {
                $line = $d->toColumnAttrsString();
                echo $formatter->format("  " . $line . "\n", $d->flag === '+' ? 'green' : 'red' );
            }
        }
    }
}

