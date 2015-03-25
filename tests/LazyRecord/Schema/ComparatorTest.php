<?php
use LazyRecord\Schema\Comparator;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\Comparator\ConsolePrinter;
use LazyRecord\Schema\ColumnDiff;

class ComparatorTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicComparison()
    {
        $a = new SchemaDeclare;
        $a->column('same');
        $a->column('changed')
            ->varchar(20);
        $a->column('removed')
            ->boolean();

        $b = new SchemaDeclare;
        $b->column('same');
        $b->column('changed')
            ->varchar(30);
        $b->column('added')
            ->varchar(10);

        $comparator = new Comparator;
        $diffs = $comparator->compare($a, $b);
        foreach ($diffs as $diff) {
            $this->assertInstanceOf('LazyRecord\Schema\ColumnDiff', $diff);
        }
        $this->assertEquals('removed',$diffs[0]->name);
        $this->assertEquals('-',$diffs[0]->flag);

        $this->assertEquals('added',$diffs[1]->name);
        $this->assertEquals('+',$diffs[1]->flag);

        /**
         * this can't work with posix (color output)
         */
        # $this->expectOutputRegex('/^= same/sm');
        # $this->expectOutputRegex('/^= changed/sm');
        # $this->expectOutputRegex('/^- removed/sm');
        
        $printer = new ConsolePrinter($diffs);
        ok($printer);

        /*
        ob_start();
        $printer->output();
        $content = ob_get_contents();
        ob_clean();
        like('#removed#',$content);
        like('#added#',$content);
        */
    }
}

