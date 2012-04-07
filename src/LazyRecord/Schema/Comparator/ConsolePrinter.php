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
        if( empty($this->diff) )
            return;
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
                $line = sprintf("%s %s",$d->flag , $d->name );
                foreach( $d->column->attributes as $property => $value ) {
                    $line .= ", $property = $value";
                }
                echo $formatter->format($line . "\n", $d->flag === '+' ? 'green' : 'red' );
            }
        }
    }
}

