<?php
use LazyRecord\Schema\Comparator;
use LazyRecord\Schema\SchemaDeclare;
use LazyRecord\Schema\Comparator\ConsolePrinter;
use LazyRecord\Schema\ColumnDiff;

class ComparatorTest extends \PHPUnit_Framework_TestCase
{

    public function testBasicComparison()
    {
        $before = new SchemaDeclare;
        $before->column('same');
        $before->column('changed')
            ->varchar(20);

        $before->column('removed')
            ->boolean();

        $after = new SchemaDeclare;
        $after->column('same');
        $after->column('changed')
            ->varchar(30);
        $after->column('added')
            ->varchar(10);

        $comparator = new Comparator;
        $diffs = $comparator->compare($before, $after);
        foreach ($diffs as $diff) {
            $this->assertInstanceOf('LazyRecord\Schema\Comparator\ColumnDiff', $diff);
        }

        $firstDiff = $diffs[0];
        $this->assertEquals('changed', $firstDiff->name);
        $this->assertEquals('=', $firstDiff->flag);

        $secondDiff = $diffs[1];
        $this->assertEquals('removed', $secondDiff->name);
        $this->assertEquals('-', $secondDiff->flag);

        $thirdDiff = $diffs[2];
        $this->assertEquals('added', $thirdDiff->name);
        $this->assertEquals('+', $thirdDiff->flag);



        /**
         * this can't work with posix (color output)
         */
        # $this->expectOutputRegex('/^= same/sm');
        # $this->expectOutputRegex('/^= changed/sm');
        # $this->expectOutputRegex('/^- removed/sm');
        /*
        ob_start();
        $printer->output();
        $content = ob_get_contents();
        ob_clean();
        like('#removed#',$content);
        like('#added#',$content);
        */
        return $diffs;
    }

    /**
     * @depends testBasicComparison
     */
    public function testPrinter($diffs)
    {
        $printer = new ConsolePrinter($diffs);
        ok($printer);
    }
}

