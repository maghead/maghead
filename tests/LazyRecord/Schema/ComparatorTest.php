<?php

class ComparatorTest extends \PHPUnit_Framework_TestCase
{
    public function testBasicComparison()
    {
        $a = new \LazyRecord\Schema\SchemaDeclare;
        $a->column('same');
        $a->column('changed')
            ->varchar(20);
        $a->column('removed')
            ->boolean();

        $b = new \LazyRecord\Schema\SchemaDeclare;
        $b->column('same');
        $b->column('changed')
            ->varchar(30);
        $b->column('added')
            ->varchar(10);

        $comparator = new LazyRecord\Schema\Comparator;
        $diff = $comparator->compare( $a , $b );
        ok( $diff );

        /**
         * this can't work with posix (color output)
         */
        # $this->expectOutputRegex('/^= same/sm');
        # $this->expectOutputRegex('/^= changed/sm');
        # $this->expectOutputRegex('/^- removed/sm');
        
        $printer = new LazyRecord\Schema\Comparator\ConsolePrinter($diff);
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

