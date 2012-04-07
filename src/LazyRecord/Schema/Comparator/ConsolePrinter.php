<?php
namespace LazyRecord\Schema\Comparator;

class ConsolePrinter
{
    public $diff = array();

    public function __construct($diff) 
    {
        $this->diff = $diff;
    }

    public function output()
    {
        $formatter = new \CLIFramework\Formatter;

        echo $formatter->format("--- old","red") , "\n";
        echo $formatter->format("+++ new","green") , "\n";
        echo "@@ @@\n";

        // printer
        foreach( $this->diff as $d ) {
            // show attribute diff
            if( $d->flag === '=' ) {
                echo "=" , ' ' , $d->name , "\n";
                foreach( $d->attrDiffs as $attrDiff ) {
                    echo $formatter->format(sprintf("\t- %s %s\n", $attrDiff->name, $attrDiff->before ),'red');
                    echo $formatter->format(sprintf("\t+ %s %s\n", $attrDiff->name, $attrDiff->after ),'green');
                }
            }
            else {
                echo $formatter->format(sprintf("%s %s\n",$d->flag , $d->name ), $d->flag === '+' ? 'green' : 'red' );
            }
        }
    }
}

