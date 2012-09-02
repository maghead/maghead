<?php
namespace LazyRecord\Schema\Comparator;

class ConsolePrinter
{
    public $diff = array();

    public $beforeName = 'old';
    public $afterName = 'new';

    public function __construct($diff) 
    {
        $this->diff = $diff;
    }

    public function output()
    {
        if( empty($this->diff) )
            return;
        $formatter = new \CLIFramework\Formatter;
        echo $formatter->format('--- ' . $this->beforeName,"strong_white") , "\n";
        echo $formatter->format('+++ ' . $this->afterName, "strong_white") , "\n";
        echo "@@ columns @@\n";

        // printer
        foreach( $this->diff as $d ) {
            // show attribute diff
            if( $d->flag == '=' ) {
                echo '=' , ' ' , $d->name , "\n";
                foreach( $d->attrDiffs as $attrDiff ) {
                    echo $formatter->format(sprintf("\t- %s %s\n", $attrDiff->name, $attrDiff->before ),'red');
                    echo $formatter->format(sprintf("\t+ %s %s\n", $attrDiff->name, $attrDiff->after ),'green');
                }
            }
            else {
                $line = sprintf('    %s %s',$d->flag , $d->name );
                foreach( $d->column->attributes as $property => $value ) {
                    if( is_object($value) ) {
                        if( $value instanceof \Closure ) {
                            $line .= ", $property = {Closure}";
                        }
                        else {
                            $line .= ", $property = " . var_export($value,true);
                        }
                    }
                    elseif(is_string($value)) {
                        $line .= ", $property = $value";
                    }
                }
                echo $formatter->format($line . "\n", $d->flag === '+' ? 'green' : 'red' );
            }
        }
    }
}

